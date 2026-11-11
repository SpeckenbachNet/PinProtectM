<?php

/**
 * @package     PinProtectM
 * @copyright   Speckenbach.net
 * @license     GNU General Public License version 2 or later
 */

namespace SpeckenbachNet\Plugin\System\PinProtect\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\OutputController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\SubscriberInterface;
use Joomla\Utilities\IpHelper;

/**
 * Locks the whole frontend behind a PIN code.
 *
 * The check runs in onAfterRoute: routing has resolved the menu item (needed for the
 * Itemid exceptions with SEF URLs), but no component has been dispatched yet.
 */
final class PinProtect extends CMSPlugin implements SubscriberInterface
{
    /** Session key holding a hash of the PIN that was entered */
    private const SESSION_KEY = 'plg_system_pinprotect.pin';

    /** Name of the PIN input in the form */
    private const INPUT_NAME = 'pinprotect_pin';

    /** Cache group for the failed-attempt counters */
    private const CACHE_GROUP = 'plg_system_pinprotect';

    public static function getSubscribedEvents(): array
    {
        return ['onAfterRoute' => 'onAfterRoute'];
    }

    public function onAfterRoute(): void
    {
        $app = $this->getApplication();
        $pin = trim((string) $this->params->get('access_pin', ''));

        if ($app->isClient('administrator')) {
            if ($pin === '') {
                $this->warnMissingPin();
            }

            return;
        }

        // Only the website; API and CLI stay untouched. Without a PIN the plugin does nothing.
        if (!$app->isClient('site') || $pin === '') {
            return;
        }

        if ($this->params->get('skip_logged_in', 0) && !$app->getIdentity()->guest) {
            return;
        }

        $session = $app->getSession();

        // The hash ties the access to the current PIN: changing the PIN locks everyone out again
        if (hash_equals($this->pinHash($pin), (string) $session->get(self::SESSION_KEY, ''))) {
            return;
        }

        if ($this->isException()) {
            return;
        }

        $this->loadLanguage();

        $error = '';

        if ($app->getInput()->getMethod() === 'POST' && $app->getInput()->post->exists(self::INPUT_NAME)) {
            $error = $this->handleSubmit($pin);
        }

        $this->renderForm($error);
    }

    /**
     * Checks a submitted PIN. Redirects on success, returns the error message otherwise.
     */
    private function handleSubmit(string $pin): string
    {
        $app = $this->getApplication();

        if (!Session::checkToken('post')) {
            return Text::_('PLG_SYSTEM_PINPROTECT_ERROR_TOKEN');
        }

        $maxAttempts = max(0, (int) $this->params->get('max_attempts', 5));
        $lockSeconds = max(1, (int) $this->params->get('lockout_minutes', 5)) * 60;
        $cache       = $maxAttempts > 0 ? $this->getCache($lockSeconds) : null;
        $cacheId     = hash('sha256', IpHelper::getIp());
        $now         = time();
        $record      = $cache ? $this->readAttempts($cache, $cacheId) : null;

        // Counters older than the lockout time are forgotten
        if ($record === null || $now - $record['last'] > $lockSeconds) {
            $record = ['count' => 0, 'last' => 0, 'until' => 0];
        }

        if ($record['until'] > $now) {
            return Text::sprintf('PLG_SYSTEM_PINPROTECT_ERROR_LOCKED', (int) ceil(($record['until'] - $now) / 60));
        }

        $submitted = trim($app->getInput()->post->getString(self::INPUT_NAME, ''));

        if ($submitted !== '' && hash_equals($pin, $submitted)) {
            if ($cache) {
                $cache->remove($cacheId, self::CACHE_GROUP);
            }

            $session = $app->getSession();

            // New session id after the PIN was accepted (session fixation)
            $session->fork();
            $session->set(self::SESSION_KEY, $this->pinHash($pin));

            // Redirect so that reloading the page does not post the form again
            $app->redirect(Uri::getInstance()->toString());
        }

        if (!$cache) {
            return Text::_('PLG_SYSTEM_PINPROTECT_ERROR_WRONG');
        }

        $record['count']++;
        $record['last'] = $now;

        if ($record['count'] >= $maxAttempts) {
            $record['count'] = 0;
            $record['until'] = $now + $lockSeconds;
        }

        $cache->store(json_encode($record), $cacheId, self::CACHE_GROUP);

        if ($record['until'] > $now) {
            return Text::sprintf('PLG_SYSTEM_PINPROTECT_ERROR_LOCKED', (int) ceil($lockSeconds / 60));
        }

        return Text::_('PLG_SYSTEM_PINPROTECT_ERROR_WRONG');
    }

    /**
     * Is the requested page on the list of menu items or URLs that stay open?
     */
    private function isException(): bool
    {
        $app = $this->getApplication();

        $allowedIds = array_filter(array_map('intval', explode(',', (string) $this->params->get('allowed_itemids', ''))));

        if ($allowedIds) {
            $active = $app->getMenu()->getActive();
            $itemId = $active ? (int) $active->id : $app->getInput()->getInt('Itemid', 0);

            if ($itemId > 0 && \in_array($itemId, $allowedIds, true)) {
                return true;
            }
        }

        $allowedUrls = array_filter(array_map('trim', preg_split('/\R/', (string) $this->params->get('allowed_urls', ''))));

        if ($allowedUrls) {
            $current = rawurldecode(Uri::getInstance()->toString(['path', 'query']));

            foreach ($allowedUrls as $url) {
                if (str_contains($current, $url)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sends the PIN page and ends the request.
     */
    private function renderForm(string $error): never
    {
        $app      = $this->getApplication();
        $language = $app->getLanguage();

        $data = [
            'error'         => $error,
            'action'        => Uri::getInstance()->toString(),
            'inputName'     => self::INPUT_NAME,
            'langTag'       => $language->getTag(),
            'rtl'           => $language->isRtl(),
            'siteName'      => (string) $app->get('sitename'),
            'header'        => (string) $this->params->get('header_content', ''),
            'footer'        => (string) $this->params->get('footer_content', ''),
            'stylesheet'    => $this->stylesheetUrl(),
            'customCss'     => (string) $this->params->get('custom_css', ''),
            'privacyNotice' => (bool) $this->params->get('privacy_notice', 1),
            'privacyUrl'    => (string) $this->params->get('privacy_url', ''),
        ];

        ob_start();
        (static function (array $data): void {
            extract($data);
            include __DIR__ . '/../../tmpl/form.php';
        })($data);
        $html = (string) ob_get_clean();

        $app->setHeader('Content-Type', 'text/html; charset=utf-8', true);
        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        $app->setBody($html);
        $app->sendHeaders();

        echo $app->getBody();

        $app->close();
    }

    /**
     * The optional stylesheet as a URL: absolute URLs as they are, paths relative to the site root.
     */
    private function stylesheetUrl(): string
    {
        $path = trim((string) $this->params->get('stylesheet', ''));

        if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        return Uri::root(true) . '/' . ltrim($path, '/');
    }

    /**
     * Backend warning while the plugin is enabled without a PIN (Control Panel and Plugins only).
     */
    private function warnMissingPin(): void
    {
        $app    = $this->getApplication();
        $option = $app->getInput()->getCmd('option', '');

        if (!\in_array($option, ['com_cpanel', 'com_plugins'], true) || !$app->getIdentity()->authorise('core.admin')) {
            return;
        }

        $this->loadLanguage();
        $app->enqueueMessage(Text::_('PLG_SYSTEM_PINPROTECT_WARNING_NO_PIN'), 'warning');
    }

    private function pinHash(string $pin): string
    {
        return hash_hmac('sha256', $pin, (string) $this->getApplication()->get('secret'));
    }

    /**
     * Cache for the failed-attempt counters, always on (independent of the global cache setting).
     */
    private function getCache(int $lockSeconds): ?OutputController
    {
        try {
            /** @var OutputController $cache */
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('output', [
                'defaultgroup' => self::CACHE_GROUP,
                'caching'      => true,
                // In minutes; generous on purpose, expiry is checked against the stored timestamps
                'lifetime'     => (int) ceil($lockSeconds / 60) * 2,
            ]);

            return $cache;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{count: int, last: int, until: int}|null
     */
    private function readAttempts(OutputController $cache, string $id): ?array
    {
        $raw = $cache->get($id, self::CACHE_GROUP);

        if (!\is_string($raw)) {
            return null;
        }

        $record = json_decode($raw, true);

        if (!\is_array($record)) {
            return null;
        }

        return [
            'count' => (int) ($record['count'] ?? 0),
            'last'  => (int) ($record['last'] ?? 0),
            'until' => (int) ($record['until'] ?? 0),
        ];
    }
}
