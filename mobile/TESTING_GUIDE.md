# Mobile App Testing Guide

## Setup

1. **Make sure XAMPP is running** with Apache and MySQL
2. **Update API_URL** in `mobile/App.js` (line 21):
   - For real device: Use your PC's local IP (e.g., `http://192.168.1.5/inventory_backup/public/api`)
   - For Android Emulator: Use `http://10.0.2.2/inventory_backup/public/api`

3. **Install dependencies**:
   ```bash
   cd mobile
   npm install
   ```

4. **Start the app**:
   ```bash
   npx expo start
   ```

## Test Scenarios

### 1. Worker Login & Features

**Login**:
- Email: `munkas@gmail.com` (or any worker account)
- Password: (their password)

**Expected Behavior**:
- ✅ Should log in successfully
- ✅ Should see assigned company name in header: "🏢 Company Name"
- ✅ Should see ONLY active inventories (status = 'active')
- ✅ Bottom navigation: 📋 Leltárak | 👤 Profil
- ✅ No "Munkások" tab (workers can't manage other workers)

**Test Profile Screen**:
- Tap "👤 Profil" in bottom navigation
- ✅ Should show user name, email
- ✅ Should show role as "👷 Munkavállaló"
- ✅ Should show "Hozzárendelt Cég" section with company name
- ✅ Should show session info (start time, activity count)

**Test Inventory Selection**:
- Go back to "📋 Leltárak"
- Tap on an active inventory
- ✅ Should open inventory detail screen
- ✅ Should see manual ID entry (no QR scanner button)
- ✅ Should be able to:
  - Enter item ID manually
  - Open item list modal
  - Record items as found/missing
  - Submit inventory

**Test Empty State**:
- If no active inventories exist, should show: "Nincs aktív leltár"

---

### 2. Employer Login & Features

**Login**:
- Email: `employer@gmail.com` (or any employer account)
- Password: (their password)

**Expected Behavior**:
- ✅ Should log in successfully
- ✅ Should see ALL inventories (active, scheduled, completed)
- ✅ Bottom navigation: 📋 Leltárak | 👥 Munkások | 👤 Profil

**Test Worker Management Screen**:
- Tap "👥 Munkások" in bottom navigation
- ✅ Should see "Szabad Munkások" section with count
- ✅ Should see "Hozzárendelt Munkások" section grouped by company
- ✅ Free workers should have "Hozzárendel" button
- ✅ Assigned workers should have "Eltávolít" button

**Test Assigning Worker**:
- Tap "Hozzárendel" on a free worker
- ✅ Should show alert with company list
- Select a company
- ✅ Should show success message
- ✅ Worker should move to "Hozzárendelt Munkások" under selected company
- ✅ Worker should disappear from "Szabad Munkások"

**Test Removing Worker**:
- Tap "Eltávolít" on an assigned worker
- ✅ Should show confirmation dialog
- Confirm removal
- ✅ Should show success message
- ✅ Worker should move back to "Szabad Munkások"

**Test Profile Screen**:
- Tap "👤 Profil"
- ✅ Should show role as "👔 Munkáltató"
- ✅ Should NOT show "Hozzárendelt Cég" (employers aren't assigned to companies)

---

### 3. Admin Login & Features

**Login**:
- Email: `admin@gmail.com`
- Password: `admin123`

**Expected Behavior**:
- ✅ Same as employer (can see all inventories, manage workers)
- ✅ Profile shows role as "⚙️ Adminisztrátor"

---

### 4. Offline Mode

**Test Offline Recording**:
1. Turn off WiFi/disconnect from network
2. ✅ Should show "🔴 Offline" status in header
3. Select an inventory and record items
4. Submit inventory
5. ✅ Should show "Offline Mód 📴" alert
6. ✅ Should show pending count: "🔴 Offline (1 várakozik)"

**Test Auto-Sync**:
1. Turn WiFi back on
2. Wait a few seconds
3. ✅ Status should change to "🟢 Online"
4. ✅ Pending submissions should auto-upload
5. ✅ Success message should appear

---

### 5. Navigation & UI

**Test Bottom Navigation**:
- ✅ Active tab should have blue underline
- ✅ Active tab text should be blue and bold
- ✅ Tapping a tab should switch screens
- ✅ Tapping same tab should not cause errors

**Test Back Navigation**:
- From Profile → Tap "← Vissza" → Should go to inventories
- From Workers → Tap "← Vissza" → Should go to inventories
- From Inventory Detail → Tap "← Vissza" → Should go to inventory list

**Test Logout**:
- Tap "Kilépés" button in any screen header
- ✅ Should show confirmation dialog
- Confirm logout
- ✅ Should clear all data and return to login screen

---

### 6. Error Handling

**Test Invalid Login**:
- Enter wrong password
- ✅ Should show "Hibás email vagy jelszó!" alert

**Test Network Error**:
- Stop XAMPP
- Try to log in
- ✅ Should show detailed network error with troubleshooting steps

**Test Connection Test**:
- On login screen, tap "🔍 Kapcsolat tesztelése"
- ✅ If XAMPP running: Should show "Kapcsolat OK! ✅"
- ✅ If XAMPP stopped: Should show "Kapcsolat HIBA! ❌" with details

**Test Worker Assignment Edge Cases**:
- Try to assign worker already assigned → Should show error
- Try to assign without companies → Should show "Nincs elérhető cég!"

---

## Known Limitations

1. ❌ **No QR Scanner** - Removed due to Expo SDK 54 issues
   - Users must enter item IDs manually or use item list
   
2. ⚠️ **Company Picker** - Uses native Alert dialog
   - Could be improved with custom modal picker

3. ⚠️ **No Pull-to-Refresh** - Only on inventory list screen
   - Profile and Workers screens don't have refresh

4. ⚠️ **No Search** - Workers screen doesn't have search/filter

---

## Troubleshooting

### "Hálózati hiba" on login
1. Check XAMPP is running
2. Check API_URL matches your setup
3. Check phone and PC are on same network
4. Try connection test button

### "Nincs elérhető leltár" for worker
- Make sure there's at least one inventory with `status = 'active'`
- Workers can ONLY see active inventories

### "Unauthorized" when managing workers
- Only employers and admins can access worker management
- Workers will see 2 tabs, employers/admins see 3 tabs

### Assigned company not showing for worker
- Make sure worker is assigned to a company in `company_user` table
- Try logging out and back in
- Check `/api/inventories.php?get_worker_company=1` endpoint

---

## Success Criteria

✅ Workers can:
- See their assigned company
- View only active inventories
- View their profile with company info
- Record and submit inventory items manually

✅ Employers/Admins can:
- See all inventories
- View free and assigned workers
- Assign workers to companies (1 company per worker)
- Remove worker assignments
- View complete worker list grouped by company

✅ All users can:
- Log in/out successfully
- Navigate between screens
- Use offline mode
- View activity logs
- See pending submissions when offline
