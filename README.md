# Driver License Management

A Nextcloud app to manage driver licenses and send automatic expiry reminders.

## Features

- Track driver information including name, surname, license number, expiry date, and phone number
- Manage notification recipients with email addresses and phone numbers
- Automatic email reminders when licenses are about to expire (30, 7, and 1 day before)
- Simple, user-friendly interface that matches Nextcloud's design
- PostgreSQL database support

## Requirements

- Nextcloud 31+
- PostgreSQL database

## Installation

### From Git Repository

1. Clone the repository into your Nextcloud apps directory:
   ```
   cd /path/to/nextcloud/apps
   git clone https://github.com/mazzofab/license-mgmt.git driverlicensemgmt
   ```

2. Enable the app:
   ```
   cd /path/to/nextcloud
   php occ app:enable driverlicensemgmt
   ```

### From Nextcloud App Store

1. Browse to Apps > Office & Text in your Nextcloud instance
2. Find "Driver License Management" and click Install

## Usage

1. After installation, a new app icon will appear in the Nextcloud navigation menu
2. Click on "Driver License Management" to access the dashboard
3. Use the "Manage Drivers" page to add, edit, and delete driver information
4. Use the "Notification Recipients" page to manage email addresses and phone numbers for expiry notifications
5. The system will automatically send reminders at 30 days, 7 days, and 1 day before license expiry

## Development

If you want to contribute to this app, you can set up a development environment as follows:

1. Set up a Nextcloud development instance
2. Clone the repository into the apps directory
3. Install dependencies:
   ```
   cd /path/to/nextcloud/apps/driverlicensemgmt
   composer install
   ```

4. Enable the app in development mode:
   ```
   php /path/to/nextcloud/occ app:enable --force driverlicensemgmt
   ```

## Background Jobs

This app uses Nextcloud's background job system to send license expiry reminders. The job runs once per day and checks for licenses that will expire in 30, 7, or 1 day.

Make sure your Nextcloud background jobs are properly configured. We recommend using Cron for production environments:

```
# Add this to your crontab
*/5 * * * * php -f /path/to/nextcloud/cron.php
```

## License

This app is licensed under the GNU Affero General Public License version 3 or later. See [LICENSE](LICENSE) for details.

## Author

- Fabrizio Mazzoni <fabrizio@fsm.co.tz>

## Support

For bugs and feature requests, please use the [issue tracker](https://github.com/mazzofab/license-mgmt/issues) on GitHub.