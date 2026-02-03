# Mobile App Setup Guide

## 🔧 Fixing the Network Error

You're getting `Network Error` and `timeout exceeded` errors because the mobile app can't connect to your XAMPP server. Here's how to fix it:

### For Android Emulator:

The API URL is currently set to: `http://10.0.2.2/inventory/public/api`

This is the **correct** address for Android Emulator to connect to your computer's localhost.

### For Physical Android Device:

1. **Find your PC's IP address:**
   - Open Command Prompt (cmd)
   - Type: `ipconfig`
   - Look for "IPv4 Address" under your active network adapter
   - Example: `192.168.1.100`

2. **Update App.js:**
   - Open `mobile/App.js`
   - Change line ~23 from:
     ```javascript
     const API_URL = 'http://10.0.2.2/inventory/public/api';
     ```
   - To:
     ```javascript
     const API_URL = 'http://YOUR_PC_IP/inventory/public/api';
     ```
   - Example: `http://192.168.1.100/inventory/public/api`

3. **Make sure:**
   - Your phone and PC are on the **same WiFi network**
   - XAMPP Apache is running
   - Windows Firewall allows connections to Apache (port 80)

### For iOS Simulator:

Use `http://localhost/inventory/public/api`

---

## ✅ Before Running the App

1. **Start XAMPP:**
   - Open XAMPP Control Panel
   - Start Apache
   - Start MySQL

2. **Test the API:**
   - Open browser
   - Go to: `http://localhost/inventory/public/api/login.php`
   - You should see a JSON error (this is normal - it means the API is working)

3. **Use the Connection Test Button:**
   - In the mobile app, on the login screen
   - Click "🔍 Kapcsolat tesztelése"
   - This will tell you if the app can reach your server

---

## 🚀 Running the App

```bash
cd C:\xampp\htdocs\inventory\mobile
npx expo start
```

Then:
- Press `a` for Android emulator
- Press `i` for iOS simulator  
- Scan QR code with Expo Go app on physical device

---

## 🐛 Troubleshooting

### "Network Error" / "timeout exceeded"
- ✅ Check XAMPP Apache is running
- ✅ Check API_URL in App.js matches your setup
- ✅ For physical device: ensure same WiFi network
- ✅ For physical device: disable Windows Firewall temporarily to test
- ✅ Use the "Kapcsolat tesztelése" button in the app

### White Screen
- ✅ Check Metro bundler logs for errors
- ✅ Reload app: shake device → "Reload"
- ✅ Clear cache: `npx expo start -c`

### Can't login even with correct credentials
- ✅ Make sure you activated your account via email
- ✅ Check database - user status should be 'active'
- ✅ Check console logs in Metro bundler

---

## 📱 Test Credentials

If you need to test, create a user account:
1. Go to: `http://localhost/inventory/public/register.php`
2. Register a new account
3. Check your email and click the activation link
4. Now you can login in the mobile app!

---

## 🔥 Windows Firewall Settings (if needed)

If your physical device can't connect:

1. Open Windows Firewall
2. Click "Advanced settings"
3. Click "Inbound Rules"
4. Click "New Rule..."
5. Rule Type: Port
6. TCP, Specific port: 80
7. Allow the connection
8. Apply to all profiles
9. Name it "XAMPP Apache"

Or just disable Windows Firewall temporarily for testing.

