# PinProtectM

One PIN in front of your whole site, no user accounts needed.

A system plugin for Joomla 6 that protects the entire website with a PIN code,
with exceptions for selected menu items and URLs. English and German included.

## Features

- Locks the whole frontend before any component runs; the backend stays open
- One PIN for all visitors, valid for the browser session
- Changing the PIN asks everyone for the new one
- Exceptions by menu item ID or by URL part (e.g. imprint, privacy policy)
- Optional: logged-in Joomla users skip the PIN
- Lock after too many wrong attempts from one IP address
- PIN page with your own header and footer (editor), custom CSS, optional stylesheet
- Optional privacy notice on the PIN page with a link to your privacy policy
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
| Allowed URLs         | One URL part per line, e.g. `/impressum`                  |
| Skip logged-in users | Logged-in Joomla users don't need the PIN                 |
| Wrong attempts       | Wrong PINs per IP address before the lock (`0` = off)     |
| Lock (minutes)       | How long entering a PIN stays locked                      |
| Header / Footer      | Content above and below the PIN box                       |
| Stylesheet           | Optional stylesheet, e.g. the `user.css` of your template |
| Custom CSS           | Additional styles for the PIN page                        |
| Privacy notice       | Show the notice, link to the privacy policy               |

Pages linked from the PIN page (imprint, privacy policy, login) have to be allowed
menu items or URLs, otherwise they ask for the PIN too.

## Good to know

PinProtectM keeps casual visitors and search engines out. It is not a replacement
for real user accounts: everyone who knows the PIN gets in.

## Updates

Joomla checks this repository's releases for updates. New versions show up under
**System → Update → Extensions**.

## Licence

GNU General Public License version 2 or later.

Speckenbach.net · software@speckenbach.net · https://software.speckenbach.net
