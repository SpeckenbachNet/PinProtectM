# PinProtectM

One PIN in front of your whole site, no user accounts needed.

A system plugin for Joomla 6 that protects the entire website with a PIN code,
with exceptions for selected menu items and URLs. English and German included.

## Features

- Locks the whole frontend before any component runs; the backend stays open
- One PIN for all visitors, valid for the browser session
- Changing the PIN asks everyone for the new one
- Exceptions by menu item ID or by URL path
- Links to imprint and privacy policy below the PIN box, both pages open without a PIN
- Optional: logged-in Joomla users skip the PIN
- Lock after too many wrong attempts from one IP address
- PIN page with your own header and footer (editor), custom CSS, optional stylesheet
- Optional privacy notice on the PIN page
- Updates via System → Update

## Requirements

- Joomla 6.0 or later
- PHP 8.3 or later

## Installation

1. Download `plg_system_pinprotect-<version>.zip` from the
   [latest release](https://github.com/SpeckenbachNet/PinProtectM/releases/latest).
2. In Joomla: **System → Install → Extensions**, upload the file.
3. **System → Plugins → System - PinProtectM**: set the PIN, then enable the plugin.

As long as no PIN is set, the plugin does nothing and shows a warning in the backend.

## Settings

| Setting              | Meaning                                                   |
|----------------------|-----------------------------------------------------------|
| PIN                  | The code visitors have to enter                           |
| Allowed menu items   | Menu item IDs without PIN, e.g. `101,105`                 |
| Allowed URLs         | One path per line, e.g. `/kontakt`                        |
| Skip logged-in users | Logged-in Joomla users don't need the PIN                 |
| Wrong attempts       | Wrong PINs per IP address before the lock (`0` = off)     |
| Lock (minutes)       | How long entering a PIN stays locked                      |
| Header / Footer      | Content above and below the PIN box                       |
| Stylesheet           | Optional stylesheet, e.g. the `user.css` of your template |
| Custom CSS           | Additional styles for the PIN page                        |
| Imprint              | Address of the imprint, e.g. `/impressum`                 |
| Privacy policy       | Address of the privacy policy, e.g. `/datenschutz`        |
| Privacy notice       | Bar at the bottom of the PIN page about the session cookie |

### Allowed URLs

Only the path of the address counts, never the query string (`?…`). An entry has to
match whole path segments:

| Entry      | Opens                                          | Does not open                 |
|------------|------------------------------------------------|-------------------------------|
| `/kontakt` | `/kontakt`, `/de/kontakt`, `/kontakt/seite-2`  | `/kontakt-alt`, `/x?a=/kontakt` |

Imprint and privacy policy are allowed automatically. Other pages linked from the
PIN page (e.g. a login page) have to be allowed menu items or URLs, otherwise they
ask for the PIN too. Non-SEF addresses (`index.php?option=…`) can't be entered as
URLs; use the menu item ID instead.

## Styling the PIN page

The PIN page loads its styles in this order; later ones win:

1. Built-in styles of the plugin (basic layout)
2. **Stylesheet** (e.g. your template's `user.css`)
3. **Custom CSS**

Classes: `.pinprotect`, `.pinprotect-header`, `.pinprotect-box`, `.pinprotect-error`,
`.pinprotect-input`, `.pinprotect-button`, `.pinprotect-links`, `.pinprotect-footer`,
`.pinprotect-notice`

Example:

```css
.pinprotect-button { background: #88A186; border-color: #88A186; }
.pinprotect-notice { background: #A391B2; }
```

## Wrong attempts

Wrong PINs are counted per IP address in Joomla's cache (group `plg_system_pinprotect`,
with the file handler in `cache/plg_system_pinprotect/`). The IP address is stored only
as a hash. This works whether caching is switched on in the Global Configuration or not.

- After the set number of wrong PINs, entering a PIN is locked for the set minutes.
- Visitors sharing one IP address (office, household) share the lock.
- The correct PIN clears the counter for that IP address.
- To lift all locks at once: **System → Clear Cache**, delete the group
  `plg_system_pinprotect`.

## Good to know

PinProtectM keeps casual visitors and search engines out. It is not a replacement
for real user accounts: everyone who knows the PIN gets in.

## Updates

Joomla checks this repository's releases for updates. New versions show up under
**System → Update → Extensions**.

## Licence

GNU General Public License version 2 or later.

Speckenbach.net · software@speckenbach.net · https://software.speckenbach.net
