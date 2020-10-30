# Craft SimplePay plugin for Craft Commerce 3.x

This plugin integrates Craft Commerce with a Hungarian payment gateway, SimplePay. 

## Requirements

This plugin requires Craft Commerce 3.2.0 or later.

## Installation & Setup

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require webmenedzser/craft-simplepay

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Craft SimplePay.

4. Go to Settings → OTP SimplePay for Craft Commerce and set up the return URLs for your site. They can be different or the same. 

5. Go to Commerce → System Settings → Gateways. Add a *New gateway*, select OTP SimplePay v2.1 (this is the API version of SimplePay), fill in the Merchant ID & Secret Key fields and save the gateway. You will find the Webhook URL on this page after save.

6. Copy the Webhook URL and paste it into SimplePay admin. If you would like to test the plugin offline, spin up an ngrok instance on your computer (ngrok http PORTNUMBER) and alter the Webhook URL accordingly. 

7. Make sure that the Currency set in Commerce is the same as in your SimplePay account. (Especially if you get 5307 error codes in the Transaction tab of the Orders.)

## Features

This plugin currently supports the following features:
- [x] Standard payments with redirection to the SimplePay payment page
- [x] Full refunds for payments (started from the Control Panel)

The plugin DOES NOT support the following features:
- [ ] Partial refunds   
- [ ] Recurring payments

## Feature Requests & Issues

If you have a feature request or experience an error, create an issue [here](https://github.com/webmenedzser/craft-simplepay/issues).

Brought to you by [dr. Ottó Radics](https://www.webmenedzser.hu)
