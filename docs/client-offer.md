# WhatsApp AI Intake Bot - Offer Document

## 1. How The System Works

This system is a WhatsApp-based intake application for a service business.
When a customer messages the WhatsApp number, the app guides them through a simple flow:

1. The customer sends a message.
2. The app replies with a language menu.
3. The customer selects a language.
4. The app shows the available services.
5. The customer selects one service.
6. Claude AI asks for the required details one by one.
7. Once all required details are collected, the bot asks for confirmation.
8. After confirmation, the request is saved in the database.
9. Staff can review the request in the admin panel.
10. The customer receives a confirmation message.

### Functional Diagram

```text
Customer
   |
   | WhatsApp message
   v
WhatsApp Cloud API
   |
   | Webhook
   v
Laravel Application
   |
   +--> Language selection
   |
   +--> Service selection
   |
   +--> Claude AI intake
   |
   +--> Save request to database
   |
   +--> Send confirmation
   v
Staff Admin Panel
   |
   +--> Review request
   +--> Update status
   +--> Add notes
```

### In Simple Words

The app does not try to do everything automatically.
It mainly does three things:

- collects customer details,
- stores them safely,
- and gives staff a clean request to handle later.

## 2. Infrastructure Cost

These are the recurring monthly costs to keep the system running:

| Item | Estimated Cost / Month | Notes |
|---|---:|---|
| Hetzner server | ~€15 | Main hosting for the Laravel app |
| Claude AI | ~€5-20 | Depends on the number of WhatsApp conversations |
| Domain | ~€1 | Average monthly cost if divided from yearly payment |
| Ploi | ~€5 | Optional, only if you want easier server management |
| WhatsApp API | €0 | WhatsApp Cloud API itself is free |

### Estimated Monthly Total

- Without Ploi: **~€21-36/month**
- With Ploi: **~€26-41/month**

### Important Note

Server, domain, and Claude usage are not development fees.
They are running costs paid every month to keep the application online.

## 3. Development Cost

This is the one-time cost for building the system.

The development price depends on:

- how many features are included,
- whether the admin panel is included,
- whether deployment is included,
- and how much customization the client wants.

### Suggested Budget Ranges

- **Tight budget / Fiverr-friendly:** **€900-1,200**
- **Balanced custom build:** **€1,500-2,500**
- **Full solution with support and extra polish:** **€3,000+**

### What Development Includes

- WhatsApp webhook integration
- language selection flow
- service selection flow
- Claude-based chat intake
- database save logic
- queue/job processing
- admin panel for staff
- request status management
- basic production setup

### Suggested Offer For A Tight Budget Client

If the client has a limited budget, a practical offer is:

- **Development / setup:** **€1,000**
- **Monthly support:** **€50-100**
- **Infrastructure and AI usage:** paid separately at cost

This keeps the project affordable for the client while still making the work worthwhile.

## 4. Summary

This project is best sold as:

- a one-time development fee for the build,
- plus monthly infrastructure costs,
- plus optional maintenance/support if the client wants ongoing help.

That structure is easy for the client to understand and easy for you to quote.

