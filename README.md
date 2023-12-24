# PteroSync

PteroSync is a leader in providing innovative solutions for server deployment and management. It simplifies and streamlines complex server processes, enabling efficient operations. With cutting-edge technology, PteroSync empowers businesses, focusing on growth while handling server allocation and deployment. It offers reliable and scalable solutions, designed to meet diverse needs.

## Features
- Automated Port Allocation
- Intelligent User Management
- Custom Server Environment Setup
- Dynamic Server Configuration
- Advanced Server Deployment

PteroSync leverages automation for server provisioning and management, simplifying complex processes for effortless server setups and configurations directly from WHMCS. It features dynamic resource allocation and a user-centric design, offering a seamless experience with user-friendly interfaces and powerful backend capabilities.

# Module Installation Guide

Follow these steps to install and configure the WHMCS module.

[Download the latest version from our GitHub releases page](https://github.com/wohahobg/PteroSync/releases)

## Step 1: Download and Unzip
Download the module zip file from the provided link and unzip it. You will find a folder named `pterosync`.

## Step 2: Upload to WHMCS
Upload the module folder to your WHMCS installation at `/path/to/whmcs/modules/servers/`.

## Step 3: Configure config.json
Configure the `config.json` file in the module folder according to your requirements.

## Step 4: Server Configuration in WHMCS
Create a WHMCS server for PteroSync:
1. Navigate to **Setup** -> **Products/Services** -> **Servers** and create a new server.
2. For **Hostname**, enter the URL of your Pterodactyl panel (e.g., panel.qgs.bg).
3. At the bottom, under **Server Details**, select **PteroSync** as the module.
4. The **Username** field can be left blank.
5. In a separate window, log in to your Pterodactyl panel and create an Application API key with the required permissions.
6. Enter this key in the **Password** field in the WHMCS server configuration.
7. If you're enabling the feature to allow customers to reboot their servers from WHMCS, create an Account API key using a Pterodactyl Admin account and enter it in the **Access Hash** field.
8. For SSL, check the **Secure** checkbox if applicable.
9. Click **Save Changes**.
10. Pterodactyl API Permission: ![PermissionImage](https://cdn.gamecms.org/platform/app_api_permission.png)
11. WHMCS Example SETUP: ![WHMCS Setup]([https://cdn.gamecms.org/platform/app_api_permission.png](https://cdn.gamecms.org/platform/connection.webp))

## Step 5: Activate and Test
Activate the module in your WHMCS admin panel. Navigate to 'Setup' -> 'Products/Services' -> 'Servers', add a new server, and select your module. Test to ensure it's working correctly.

## Step 6: Read our documentation
For more detailed information and troubleshooting tips, please refer to our comprehensive documentation:
[Read Documentation](https://github.com/wohahobg/PteroSync/wiki)
