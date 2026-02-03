# 🚀 Gyors Indítási Útmutató

## Backend (Web)

### Indítás
1. **XAMPP indítása**
   - Nyisd meg a XAMPP Control Panel-t
   - Start: Apache + MySQL

2. **Belépés**
   - URL: `http://localhost/inventory/public/login.php`
   - Email: (regisztrált felhasználó)
   - Jelszó: (jelszavad)

### Új Funkciók Kipróbálása

#### ✅ Leltár Összegzés
1. Válassz egy céget
2. Indíts vagy válassz egy leltárt
3. Leltározz néhány eszközt
4. Fejezd be a leltárt
5. Automatikus átirányítás az összegzésre

**URL:** `http://localhost/inventory/public/worker/inventory_summary.php?inventory_id=1&company_id=1`

#### ✅ Email Értesítések
1. Válassz egy céget
2. Hozz létre új leltárt vagy indíts egyet
3. Ellenőrizd az emaileket (Mailtrap vagy SMTP)

**Beállítás:** `app/config/config.php`

#### ✅ Hiány/Probléma Lista
1. Leltározz eszközöket
2. Jelölj néhányat "Hiányzik"-nak
3. Adj hozzá megjegyzéseket (pl. "sérült")
4. Befejezd a leltárt
5. Kattints "🚨 Hiány és Probléma Lista"-ra
6. Nyomtasd ki PDF-be

**URL:** `http://localhost/inventory/public/worker/inventory_problems.php?inventory_id=1&company_id=1`

---

## Mobile App

### Első Indítás
```bash
cd mobile
npm install
npm start
```

### API URL Beállítás
**Fájl:** `mobile/App.js` (22. sor)

```javascript
// Android Emulator
const API_URL = 'http://10.0.2.2/inventory/public/api';

// Valódi eszköz (cseréld ki!)
const API_URL = 'http://192.168.1.XXX/inventory/public/api';
```

**IP megtalálása:**
```bash
# Windows
ipconfig

# Mac/Linux
ifconfig
```

### Funkciók Tesztelése

#### ✅ Offline Mód
1. Jelentkezz be
2. **Kapcsold ki a WiFi-t**
3. Leltározz eszközöket
4. Nézd a státuszt: 🔴 Offline (X várakozik)
5. **Kapcsold vissza a WiFi-t**
6. Húzd le a listát (pull-to-refresh)
7. Automatikus szinkronizálás!

#### ✅ Fotó (Sérült Eszköz)
1. QR kód olvasás
2. Válaszd: **⚠️ Sérült/Hibás**
3. Írd le a problémát: "Törött képernyő"
4. Rögzítsd
5. Az eszköz megjelenik sárga háttérrel

#### ✅ Időnyilvántartás
Automatikus! Minden esemény naplózva van:
- Belépés
- Leltár választás
- QR olvasás
- Kilépés

**Megtekintés:**
```javascript
// React Native Debugger Console
AsyncStorage.getItem('activityLog').then(console.log)
```

---

## 🎬 Teljes Demo Forgatókönyv

### 1. Munkáltató (Employer)

```
1. Bejelentkezés
   ↓
2. Cég kiválasztása
   ↓
3. Új leltár létrehozása: "2026 Januári Leltár"
   ↓
4. Email értesítés kimegy a munkásoknak 📧
   ↓
5. Leltár indítása
   ↓
6. Második email kimegy 📧
   ↓
7. Várakozás... (munkások leltároznak)
   ↓
8. Leltár befejezése
   ↓
9. Összegzés megtekintése 📊
   ↓
10. Hiány/Probléma lista letöltése 📄
```

### 2. Munkás (Mobil)

```
1. App indítása
   ↓
2. Bejelentkezés (időnyilvántartás START)
   ↓
3. Leltár kiválasztása
   ↓
4. QR kód olvasás
   ↓
   ├─ Megvan ✅
   ├─ Hiányzik ❌ (megjegyzés)
   └─ Sérült ⚠️ (fotó + leírás)
   ↓
5. Submit gomb
   ↓
   ├─ Online → Azonnal feltöltve
   └─ Offline → Mentve, később sync
   ↓
6. Kilépés (időnyilvántartás END)
```

### 3. Offline Tesztelés

```
1. Bejelentkezés
   ↓
2. Leltár választás
   ↓
3. WiFi OFF 📴
   ↓
4. QR kód olvasások (5-10 db)
   ↓
5. Státusz: 🔴 Offline (5 várakozik)
   ↓
6. Submit → "Offline Mód" alert
   ↓
7. WiFi ON 🟢
   ↓
8. Pull-to-refresh
   ↓
9. "Szinkronizálva ✅"
```

---

## 📊 Tesztadatok

### Teszt Felhasználók
```
Employer:
- Email: employer@test.com
- Pass: password123

Worker:
- Email: worker@test.com  
- Pass: password123
```

### Teszt Eszközök QR Kódok
```
room=1;item_name=Laptop
room=1;item_name=Monitor
room=2;item_name=Printer
```

---

## 🐛 Gyakori Hibák

### "Network Error" a mobilon
```
✅ Ellenőrizd:
- XAMPP fut?
- Apache elindult?
- API_URL helyes?
- Ugyanazon WiFi?
- Firewall?
```

**Teszt:**
```javascript
// App.js-ben a "Kapcsolat tesztelése" gomb
```

### Email nem megy ki
```
✅ Ellenőrizd:
- config.php MAIL_HOST, MAIL_USER, MAIL_PASS
- Mailtrap.io beállítások
- PHPMailer installed? (composer install)
```

### Offline sync nem működik
```
✅ Megoldás:
1. Pull-to-refresh
2. Kijelentkezés + visszajelentkezés
3. AsyncStorage törlése:
   AsyncStorage.clear()
```

### Összegző oldal üres
```
✅ Ellenőrizd:
- Leltározott eszközök vannak?
- inventory_id URL paraméter helyes?
- Adatbázis kapcsolat OK?
```

---

## 📱 Hasznos Parancsok

### Mobile Development
```bash
# App indítás
npm start

# Android build
npm run android

# iOS build  
npm run ios

# Cache törlés
npm start -- --clear

# AsyncStorage törlés
# App-ban futtatd:
AsyncStorage.clear()
```

### Backend
```bash
# Composer install
composer install

# PHP syntax check
php -l public/api/submissions.php

# Apache restart
# XAMPP Control Panel-ben
```

### Debugging
```bash
# Android logs
adb logcat | grep ReactNative

# Chrome DevTools
# Expo Debugger automatikusan nyílik
```

---

## 📚 További Dokumentáció

- **Teljes változásnapló:** `CHANGELOG.md`
- **Implementációs összegző:** `IMPLEMENTATION_SUMMARY.md`
- **Mobil telepítés:** `mobile/INSTALL.md`
- **API dokumentáció:** `docs/swagger.json` (frissítés szükséges)

---

## ✅ Checklist

- [ ] XAMPP fut
- [ ] Apache + MySQL elindult
- [ ] Composer dependencies telepítve
- [ ] Email beállítások konfigurálva
- [ ] Mobile npm install lefutott
- [ ] API URL beállítva a mobilon
- [ ] Tesztfelhasználók létrehozva
- [ ] Tesztadatok feltöltve

---

**Jó leltározást!** 📦✨

---
Készítette: GitHub Copilot
Verzió: 2.0.0
Dátum: 2026. Január 14.
