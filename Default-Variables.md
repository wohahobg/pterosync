# 🛠 Default Server Variables

This section allows you to set **default server variables**. These variables are crucial for configuring server settings, such as **player limits, environmental parameters, or other custom settings**.

> ⚠ **Important:** The variables used here **must** match those available in your **Pterodactyl Egg settings**. This ensures that the variables in your JSON configuration are recognized and applied correctly by **Pterodactyl**.

## 📌 JSON Format
Default variables should be defined using **JSON format** for structured and clear configuration.

### ✅ Example:
```json
{
  "MAX_PLAYERS": "30"
}
```
💡 **Make sure that variables like** `MAX_PLAYERS` **exist in your Pterodactyl Egg settings**. Otherwise, they won’t work.

### 🛠 Validating Your JSON
To ensure your JSON is formatted correctly, use [JSONLint](https://jsonlint.com) ✅.

---

## 🎛 WHMCS Configurable Options

You can use **Default Variables** in your **WHMCS Configurable Options**. Just ensure that your **Configurable Option exists in your Default Variables JSON and pterodacatyl Egg by itself.**, as shown above.

## Configurable option example (community provided).
https://cdn.discordapp.com/attachments/1213220892146933823/1429554172910637198/image.png?ex=68f73852&is=68f5e6d2&hm=77a4e53f6ec68e46657b6c54aa8428ab6db677dc400ca2d4eb58e24f999b279a&

As mentioned above use the default variables to map the configurable option. Recommended setup is making a configurable option group for each individual value for example all servers with 10GB storage get the 10GB configurable option group.

As seen in the example you can use a pipe (|) to give good display names for both option field and option name.