# Twilio WhatsApp Notifications for WooCommerce

Advanced WooCommerce WhatsApp notification plugin powered by Twilio API.

This plugin automatically sends WhatsApp notifications for WooCommerce order updates and customer account events using Twilio WhatsApp messaging.

---

## Features

- New order notifications
- Processing order notifications
- Completed order notifications
- Cancelled order notifications
- Failed payment notifications
- New customer account notifications
- Admin and customer WhatsApp alerts
- Twilio Content SID support
- WhatsApp template support
- Dynamic WooCommerce order variables
- Test WhatsApp message tool
- Notification logs inside WooCommerce
- Country code auto-formatting
- Editable message templates
- Enable or disable individual notifications

---

## Supported Variables

```text
{customer_name}
{order_id}
{order_total}
{order_status}
{site_name}
{items_list}
```

---

## Requirements

- WordPress
- WooCommerce
- Twilio account
- Twilio WhatsApp sender
- Approved WhatsApp templates for production use

---

## Setup

1. Install and activate the plugin
2. Go to **WooCommerce → Settings → WhatsApp**
3. Add your:
   - Twilio SID
   - Twilio Auth Token
   - Twilio WhatsApp sender number
   - Admin WhatsApp number
4. Configure notification messages
5. Add approved Twilio Content SID values for production WhatsApp templates
6. Enable the notifications you want to send

---

## Supported Notifications

### Admin Notifications

- New order received
- Failed payment
- Cancelled order

### Customer Notifications

- Order received
- Order processing
- Order completed
- New account created

---

## Twilio WhatsApp Support

Supports:

- Twilio Sandbox
- Production WhatsApp sender
- WhatsApp Content Templates
- Dynamic Content Variables
- Twilio Content SID API

---

### Example Twilio Template 

Hi {{1}},

Your order #{{2}} is now {{4}}.

Order total: {{3}}

Thank you for shopping with us.

For Twilio approved WhatsApp templates, use numbered variables:

{{1}} = customer_name
{{2}} = order_id
{{3}} = order_total
{{4}} = order_status
{{5}} = site_name
{{6}} = items_list

Note: For WhatsApp template approval, avoid using {{6}} / {items_list} in your first template because long dynamic item lists may be rejected.

## Example Customer Notification

```text
Hi John Smith,

Your order #1254 has been received.

Order total: AED 2,450

Thank you for shopping with us.
```

---

## Example Admin Notification

```text
Hello Admin,

A new WooCommerce order has been received.

Order number: 1254
Customer: John Smith
Order total: AED 2,450

Please check the WooCommerce dashboard for full order details.
```

---

## Author

**Virtual Qube Technologies** -  Ankit Prajapati - Founder

Website: https://vqubetech.com

---
