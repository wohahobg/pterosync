# PteroSync
[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/G2G7V5BCQ)

PteroSync is a trailblazer in server deployment and management, offering advanced solutions that make complex server processes simpler and more efficient. Our technology empowers businesses to focus on growth by efficiently handling server allocation and deployment. Designed to meet a wide range of business needs, our scalable and reliable solutions are at the forefront of server management technology.

This module is an enhanced version of the original [Pterodactyl WHMCS module](https://github.com/pterodactyl/whmcs). It builds on the solid foundation of Pterodactyl, known for its robust and user-friendly server management capabilities, by adding additional features and improvements to further refine server management tasks. Among these enhancements is the support for multiple port allocations, catering to games and applications that require complex networking setups.

## Technical Requirements

- **PHP Version**: PHP 8.0 or higher.
- **WHMCS Compatibility**: Works with versions of WHMCS that support PHP 8.0 or higher.

## Wiki

Explore the [PteroSync Wiki](https://github.com/wohahobg/PteroSync/wiki) for quick setup, configuration, and troubleshooting guides on integrating WHMCS with Pterodactyl.

## Enhanced Features

- **Automated Port Allocation**: Simplifies server setup by automatically assigning ports, minimizing manual configuration.

- **Intelligent User Management**: Efficiently handles user accounts and permissions, ensuring streamlined operations and robust security.

- **Custom Server Environment Setup**: Provides the ability to tailor server environments to specific needs.

- **Dynamic Server Configuration**: Dynamically adjusts server settings to optimize performance and resource management.

- **Advanced Server Deployment**: Utilizes state-of-the-art techniques for fast and effective server deployment.

- **Port Offset Support**: Offers enhanced port configuration options, ideal for applications requiring a query port that is offset from the main server port. This feature improves network setup flexibility and connectivity.

- **Multiple Port Allocation**: Facilitates the allocation of multiple ports for complex networking requirements, a significant feature for games and applications with specific port needs. For more details, see our [FAQ section](#FAQ).

- **Default Variables Support**: Enables pre-setting common variables for faster and more consistent server setups.

- **Game Server Status**: Enables the display of game server status, including current player count and online/offline status, providing real-time insights directly within your panel.

- **WHMCS Pterodactyl Sync**: Offers a comprehensive solution for syncing client data between WHMCS and Pterodactyl, ensuring a cohesive ecosystem. For setup guidance, visit our [Setup Guide](https://github.com/wohahobg/PteroSync/wiki/WHMCS‐Pterodactyl-Sync-Setup).


Leverage the PteroSync module to upgrade your server management experience, utilizing the latest innovations for the ever-evolving demands of today's business environment.

# Module Installation Guide

Follow these steps to install and configure the PteroSync module.

[Download the latest version from our GitHub releases page](https://github.com/wohahobg/PteroSync/releases)

## Step 1: Download and Unzip
Download the module zip file and unzip it to find the `pterosync` folder.

## Step 2: Upload to WHMCS
Upload the `pterosync` folder to your WHMCS installation at `/path/to/whmcs/modules/servers/`

## Step 3: Configure config.json
Adjust the `config.json` in the module folder as needed.
Checkout our [Config Key Guide](https://github.com/wohahobg/PteroSync/wiki/Config-Descriptions)

## Step 4: Server Configuration in WHMCS
Set up a WHMCS server for PteroSync:
1. Go to **Setup** -> **Products/Services** -> **Servers** and create a new server.
2. Enter your Pterodactyl panel URL as the **Hostname**.
3. Select **PteroSync** as the module under **Server Details**.
4. Leave the **Username** field blank.
5. In Pterodactyl, create an Application API key with necessary permissions and put it in the **Password** field of WHMCS.
6. If you enable customer server reboot from WHMCS, create an Account API key using a Pterodactyl Admin account and put it in the **Access Hash** field.
7. Check the **Secure** checkbox for SSL if applicable.
8. Click **Save Changes**.
9. Pterodactyl API Permission: ![PermissionImage](https://cdn.gamecms.org/platform/app_api_permission.png)
10. WHMCS Example SETUP: ![WHMCS Setup](https://cdn.gamecms.org/platform/whmcs-connection.png)

## Step 5: Activate and Test
Activate the module in WHMCS and test for proper functionality.

## Step 6: Read our documentation
For detailed information and troubleshooting, refer to our documentation:
[Read Documentation](https://github.com/wohahobg/PteroSync/wiki)


# FAQ

## My Game Requires Multiple Ports Allocated

Our module supports games or applications that require multiple ports. We've implemented a comprehensive solution to facilitate the allocation of multiple ports as needed. For detailed guidance on setting this up, please refer to the following sections of our documentation:

- **Ports Ranges**: Understand how to define and allocate ranges of ports for your game or application. [Ports Ranges Documentation](https://github.com/wohahobg/PteroSync/wiki/Ports-Ranges)

- **Server Port Offset**: Learn about setting up port offsets, which is crucial for games or applications that require a query port based on the server's main port number. [Server Port Offset Documentation](https://github.com/wohahobg/PteroSync/wiki/Server-Port-Offset)

- **Examples**: See practical examples of how to configure and use these features. [Examples Documentation](https://github.com/wohahobg/PteroSync/wiki/Examples)


## Overwriting Values Through Configurable Options
Values can be overwritten using either Configurable Options or Custom Fields.

The name should exactly match what you want to overwrite. For example, `dedicated_ip` will overwrite the `dedicated_ip` value based on its selection. Valid options include `server_name`, `memory`, `swap`, `io`, `cpu`, `disk`, `nest_id`, `egg_id`, `pack_id`, `location_id`, `dedicated_ip`, `ports_ranges`, `image`, `startup`, `databases`, `allocations`, `backups`, `oom_disabled`, `username`.

This approach also applies to any environment variable name. For instance, `Player Slots` will overwrite the environment variable named "Player Slots" to its value.

Useful trick: You can use the | seperator to change the display name of the variable like this: dedicated_ip|Dedicated IP => Will be displayed as "Dedicated IP" but will work correctly.

## Couldn't find any nodes satisfying the request
This can be caused from any of the following: Wrong location, not enough disk space/CPU/RAM, or no allocations matching the provided criteria.

### Discord Server
Join our Discord Server [Click Me](https://discord.com/invite/ABGVfZ7a5u) for support and queries.
