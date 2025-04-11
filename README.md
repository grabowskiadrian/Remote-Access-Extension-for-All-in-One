# All-in-One WP Migration Remote Access Extension

An extension for the **All-in-One WP Migration** plugin that allows you to remotely download the latest backup using an authorization token.

## Description

This plugin was created as a hobby project to allow for remote backup downloads from **All-in-One WP Migration** to a **TrueNAS server**. It provides a simple way to access and download the latest backup via an HTTP request with a valid access token.

## Installation

1. Install and activate the plugin.
2. Go to the **All-in-One WP Migration** settings and click on **Remote Access**.
3. Copy the generated **Access Token**.

![Alt text](./screenshot/wp-admin.png?raw=true "Admin panel")

## Usage

To download the latest backup, use the following command:

```bash
wget --content-disposition 'https://yourdomain.com/wp-admin/admin-post.php?action=remote_backup&token=YOUR_ACCESS_TOKEN'
```

Replace yourdomain.com with your site domain and YOUR_ACCESS_TOKEN with the token from the plugin settings.

## Security
The plugin uses a unique access token to secure remote requests. Ensure the token is kept safe.
