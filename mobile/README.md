# Leltár Mobile App

Simple inventory management app built with Expo SDK 54.

## Quick Start

```bash
cd mobile
npm install
npx expo start --go
```

Scan the QR code with Expo Go on your phone.

## Features

- ✅ Login with existing backend credentials
- ✅ View available inventories
- ✅ Record items as found/missing
- ✅ Manual item entry by ID
- ✅ Browse full item list
- ✅ Submit inventory data

## Configuration

Update the API URL in `App.js` (line 17):

```javascript
const API_URL = 'http://YOUR_SERVER_IP/inventory/public/api';
```
