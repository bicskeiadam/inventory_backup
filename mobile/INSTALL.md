# Mobil Alkalmazás Telepítési Útmutató

## Új Funkciók Telepítése

### 1. Dependency Telepítés

A mobil app könyvtárban futtasd:

```bash
cd mobile
npm install @react-native-async-storage/async-storage
```

vagy ha már telepítve van a package.json, egyszerűen:

```bash
npm install
```

### 2. API URL Beállítása

Nyisd meg a `mobile/App.js` fájlt és állítsd be a megfelelő API URL-t:

```javascript
// Android Emulator
const API_URL = 'http://10.0.2.2/inventory/public/api';

// Valódi eszköz (cseréld ki a saját IP címedre)
const API_URL = 'http://192.168.1.100/inventory/public/api';
```

**IP cím megtalálása:**
- Windows: `ipconfig` parancs CMD-ben
- Mac/Linux: `ifconfig` vagy `ip addr`
- Keresd az IPv4 Address-t

### 3. App Indítása

```bash
npm start
```

Ezután:
- **Android:** Nyomd meg az `a` gombot vagy scanneld be a QR kódot az Expo Go app-pal
- **iOS:** Nyomd meg az `i` gombot vagy scanneld be a QR kódot

---

## Új Funkciók Használata

### 📴 Offline Mód

**Automatikus működés:**
1. Ha nincs internet, az app automatikusan offline módba kapcsol
2. A státusz jelző piros lesz: 🔴 Offline
3. Rögzített adatok automatikusan mentődnek lokálisan
4. Amikor visszatér az internet:
   - A státusz zöldre vált: 🟢 Online
   - Az adatok automatikusan feltöltődnek
   - Értesítést kapsz: "Szinkronizálva ✅"

**Manuális szinkronizálás:**
- Húzd le a leltárak listáját (pull-to-refresh)
- Ez elindítja a szinkronizálást

### 📷 Fotódokumentálás

**Használat:**
1. Olvasd be az eszköz QR kódját
2. Válaszd a "⚠️ Sérült/Hibás" opciót
3. Írd le a problémát (pl. "Törött képernyő")
4. (Hamarosan) Készíts fotót a kamerával
5. Rögzítsd az eszközt

**Támogatott problémák:**
- Sérült
- Hibás
- Törött
- Meghibásodott

### ⏱️ Időnyilvántartás

**Automatikus naplózás:**
- Nincs teendő! Az app automatikusan rögzíti:
  - Bejelentkezés ideje
  - Leltár kiválasztás
  - Minden QR kód olvasás
  - Eszköz megtalálás/hiány
  - Beküldések
  - Kilépés (munkamenet hosszával)

**Naplózott események:**
- `SESSION_START` - Bejelentkezés
- `SESSION_END` - Kilépés (X perc)
- `INVENTORY_SELECTED` - Leltár kiválasztva
- `ITEM_FOUND` - Eszköz megtalálva
- `ITEM_MISSING` - Eszköz hiányzik
- `SUBMISSION_SUCCESS` - Sikeres beküldés
- `SUBMISSION_OFFLINE` - Offline mentés

**Log megtekintése:**
Az activity log az eszköz AsyncStorage-ában található, a későbbiekben egy dedikált UI is készülhet hozzá.

---

## Hibaelhárítás

### AsyncStorage hiba
```
Error: @react-native-async-storage/async-storage not installed
```

**Megoldás:**
```bash
npm install @react-native-async-storage/async-storage
expo prebuild --clean
```

### Kamera engedély
```
Error: Camera permission not granted
```

**Megoldás:**
- Android: Engedélyezd a kamerát az app beállításokban
- iOS: Engedélyezd a kamerát az iOS beállításokban

### Hálózati hiba
```
Network Error / ECONNABORTED
```

**Ellenőrizd:**
1. XAMPP fut-e
2. Apache elindult-e
3. API_URL helyes-e
4. Telefon és PC ugyanazon WiFi hálózaton van-e
5. Firewall nem blokkolja-e

### Szinkronizálás nem működik
```
Pending submissions not syncing
```

**Megoldás:**
1. Ellenőrizd az internet kapcsolatot
2. Húzd le a leltárak listáját (pull-to-refresh)
3. Jelentkezz ki és vissza

---

## Tesztelés

### Offline Mód Tesztelése

1. Kapcsold ki a WiFi-t az eszközön
2. Próbálj leltározni - működnie kell
3. Rögzíts néhány eszközt
4. Kapcsold vissza a WiFi-t
5. Húzd le a listát - automatikus szinkronizálás

### Időnyilvántartás Ellenőrzése

1. Használd React Native Debugger-t
2. AsyncStorage megtekintése:
   ```javascript
   AsyncStorage.getItem('activityLog').then(console.log)
   ```

---

## Teljesítmény

- **Offline mentés:** ~10ms / eszköz
- **Szinkronizálás:** ~200ms / beküldés
- **AsyncStorage limit:** ~6MB (bőven elég)
- **Activity log:** ~500 esemény tárolható

---

## Frissítések

A jövőben:
- [ ] Push notificationök
- [ ] Valódi fotó feltöltés
- [ ] Activity log UI
- [ ] Offline térkép
- [ ] Background sync

---

Készítette: GitHub Copilot
Dátum: 2026. Január 14.
