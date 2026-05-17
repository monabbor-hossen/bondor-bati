# Bondor Bati POS - Deployment Guide

## Overview
This guide explains how to set up the Bondor Bati POS system on a local network for use in a food cart operation. No internet connection is required.

---

## Prerequisites
- Ubuntu laptop (or any Linux/Windows machine with PHP)
- Wi-Fi router (any basic router will work)
- Staff smartphones (Android/iOS)
- 80mm thermal receipt printer (optional, for physical printing)

---

## Step 1: Database Setup

1. Start Apache/MySQL on your laptop:
   ```bash
   sudo service mysql start
   ```

2. Open browser and run the setup scripts:
   ```
   http://localhost/setup_database.php
   http://localhost/setup_admin.php
   http://localhost/database_seeder.php
   ```

---

## Step 2: Start the PHP Server

In the project directory, run:

```bash
cd /opt/lampp/htdocs/bondor-bati
php -S 0.0.0.0:8000
```

The server is now running on **all network interfaces** at port 8000.

---

## Step 3: Network Setup (Wi-Fi Router)

### Option A: Connect Router to Laptop (Wired)
1. Connect the router's WAN port to the laptop's ethernet port
2. Set router to "Bridge Mode" or disable DHCP on the router
3. The laptop's network will serve both the router and connected devices

### Option B: Router as Access Point
1. Connect router to laptop via ethernet cable
2. Set router's IP to match your laptop's subnet (e.g., 192.168.1.254)
3. Disable the router's DHCP server

---

## Step 4: Find Your Local IP Address

Run this command on the laptop:

```bash
hostname -I
```

Example output: `192.168.1.100`

This is the IP staff will type into their phones.

---

## Step 5: Staff Access

On each staff smartphone:

1. Connect to the same Wi-Fi network as the laptop
2. Open Chrome or Safari
3. Enter: `http://192.168.1.100:8000` (replace with your IP)
4. Login with credentials:
   - **Admin:** `admin` / `password123`
   - **Staff:** `staff` / `staff123`

---

## Receipt Printing

When a sale completes, the receipt page auto-loads and triggers the print dialog:

1. Ensure a printer is connected to the laptop
2. Set the browser's print destination to your thermal printer
3. The print CSS locks width to 80mm for thermal paper

---

## Quick Reference Commands

| Task | Command |
|------|---------|
| Start PHP server | `php -S 0.0.0.0:8000` |
| Find local IP | `hostname -I` |
| Start MySQL | `sudo service mysql start` |
| Restart server | Stop (Ctrl+C) and run start command again |

---

## Troubleshooting

- **Staff can't connect**: Ensure they're on the same Wi-Fi network
- **Server won't start**: Check if port 8000 is in use `lsof -i :8000`
- **Database errors**: Run setup scripts again in order
- **Print issues**: Set default printer in browser before first sale

---

## System Architecture

```
┌─────────────────┐      Ethernet/Wi-Fi       ┌─────────────────┐
│   Laptop        │ ◄─────────────────────────► │  Wi-Fi Router   │
│   (Server)      │                            │  (Bridge/AP)    │
│   - PHP         │                            └────────┬────────┘
│   - MySQL       │                                     │
│   - Apache      │                              Wi-Fi │
└─────────────────┘                                    │
     │                                                 ▼
     │                                         ┌─────────────┐
     │                                         │   Staff     │
     │                                         │  Smartphone │
     │                                         │  (Chrome)   │
     │                                         └─────────────┘
     │
     ▼
┌─────────────┐
│  Thermal    │
│  Printer    │
└─────────────┘
```

---

**Ready to serve!** 🎉