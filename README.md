# Module Installation Guide

Follow these steps to install and configure the WHMCS module.

[Download from our GitHub page](https://github.com/wohahobg/PteroSync/releases/tag/1.0.0)

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

Ensure the API keys are correctly set as shown above.

## Step 5: Activate and Test
Activate the module in your WHMCS admin panel. Navigate to 'Setup' -> 'Products/Services' -> 'Servers', add a new server, and select your module. Test to ensure it's working correctly.

## Step 6: Read our documentation
For more detailed information and troubleshooting tips, please refer to our comprehensive documentation.

[Read Documentation](#) <!-- Replace '#' with the actual URL to your documentation -->
