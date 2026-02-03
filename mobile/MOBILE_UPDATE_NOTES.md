# Mobile App - Feature Updates

## Updated: Mobile App with Full Web Functionality

### Changes Made

#### 1. **Removed QR Scanner** ❌
- Removed `expo-camera` dependency from imports
- Deleted QR scanner modal and all scanner-related functions
- Removed `showScanner`, `scanned`, and `permission` state variables
- Removed scanner button from inventory screen
- Removed all scanner-related styles (btnScanner, orDivider, scanner container, etc.)
- **Reason**: Expo SDK 54 compatibility issues

#### 2. **Added Role-Based Navigation** 🧭
- New state: `currentScreen` - tracks current screen (inventories, profile, workers)
- Bottom navigation bar with 2-3 tabs:
  - **Workers**: 📋 Leltárak (Inventories)
  - **Employers/Admin**: 📋 Leltárak + 👥 Munkások (Workers)
  - **All roles**: 👤 Profil (Profile)

#### 3. **Profile Screen** 👤
- Displays user information:
  - Name
  - Email
  - Role (with emoji: 👷 Worker, 👔 Employer, ⚙️ Admin)
- **For Workers**: Shows assigned company name
- Session information:
  - Session start time
  - Activity log count
  - Pending submissions count (if offline)

#### 4. **Worker Assignment Screen** 👥 (Employer/Admin Only)
- Two sections:
  - **Free Workers**: Workers not assigned to any company
    - Shows name and email
    - "Hozzárendel" (Assign) button
    - Selecting a worker shows company picker
  - **Assigned Workers**: Grouped by company
    - Shows company name as header (🏢 Company Name)
    - Lists workers under each company
    - "Eltávolít" (Remove) button to unassign

#### 5. **Active Inventories Filter** 📋
- **Workers**: See only `status = 'active'` inventories
- **Employers/Admin**: See all inventories
- Updated in `fetchInventories()` function
- Empty state shows different message for workers: "Nincs aktív leltár"

#### 6. **Worker Company Display** 🏢
- Workers see their assigned company in the header
- Displayed as: "🏢 Company Name" below online status
- Fetched automatically on login via new API endpoint

#### 7. **New API Endpoints** 🔌
Added to `/public/api/inventories.php`:

```php
GET /inventories.php?get_worker_company=1
// Returns: { company: { id, name, ... } }

GET /inventories.php?get_companies=1
// Returns: { companies: [...] }

GET /inventories.php?get_workers=1
// Returns: { free_workers: [...], assigned_workers: [...] }

POST /inventories.php
// Body: { action: 'assign_worker', worker_id: X, company_id: Y }
// Returns: { success: true }

POST /inventories.php
// Body: { action: 'remove_worker', worker_id: X }
// Returns: { success: true }
```

#### 8. **New State Variables** 📦
```javascript
// Navigation
const [currentScreen, setCurrentScreen] = useState('inventories');

// Worker/Company data
const [assignedCompany, setAssignedCompany] = useState(null);
const [freeWorkers, setFreeWorkers] = useState([]);
const [assignedWorkers, setAssignedWorkers] = useState([]);
const [companies, setCompanies] = useState([]);
const [selectedCompanyId, setSelectedCompanyId] = useState(null);
```

#### 9. **New Functions** 🔧
- `fetchAssignedCompany()` - Get worker's assigned company
- `fetchCompanies()` - Get all companies
- `fetchWorkers()` - Get free and assigned workers
- `assignWorker(workerId, companyId)` - Assign worker to company
- `removeWorkerAssignment(workerId)` - Remove worker assignment
- `renderProfileScreen()` - Profile screen UI
- `renderWorkersScreen()` - Worker management UI
- `renderInventoryListScreen()` - Inventory list with navigation
- `renderInventoryScreen()` - Inventory detail screen
- `renderLoginScreen()` - Login screen (refactored)

#### 10. **Updated Login Flow** 🔐
After successful login, the app now:
1. Fetches inventories (existing)
2. **Workers**: Fetches assigned company
3. **Employers/Admin**: Fetches companies and workers

### New Styles Added

```javascript
// Profile
infoRow, infoLabel, infoValue, divider

// Workers
workerItem, workerName, workerMeta, companyTitle

// Navigation
bottomNav, navBtn, navBtnText, navBtnActive, navBtnActiveText

// Other
companyInfo, btnDanger
```

### Removed Dependencies
- `expo-camera` - No longer imported or used

### Migration Notes

⚠️ **Breaking Changes**:
- QR scanner functionality completely removed
- Manual ID entry is now the only way to record items (besides the item list modal)

✅ **Backwards Compatible**:
- Existing offline mode, activity logging, and submission features unchanged
- API authentication and data structures remain the same
- Existing web pages continue to work without changes

### Testing Checklist

- [ ] Worker login → See assigned company in header
- [ ] Worker → Only see active inventories
- [ ] Worker → Profile shows company name
- [ ] Employer login → See all inventories
- [ ] Employer → Workers tab shows free/assigned workers
- [ ] Employer → Can assign worker to company
- [ ] Employer → Can remove worker assignment
- [ ] Admin → Same as employer
- [ ] Offline mode still works
- [ ] Activity logging still works
- [ ] Manual item entry still works
- [ ] Item list modal still works
- [ ] Bottom navigation switches screens correctly

### Known Limitations

1. No QR scanner - users must enter IDs manually or use item list
2. Company picker uses Alert.alert (native dialog) - could be improved with custom modal
3. No pull-to-refresh on profile/workers screens (only inventory list)
4. No search functionality on workers screen

### Future Enhancements

- [ ] Add QR scanner back when Expo SDK is updated
- [ ] Add search/filter on workers screen
- [ ] Add pagination for large worker lists
- [ ] Custom company picker modal instead of Alert
- [ ] Pull-to-refresh on all screens
- [ ] Add worker statistics to profile
- [ ] Add company details screen
- [ ] Offline support for worker assignments

### File Changes

**Modified Files**:
- `mobile/App.js` - Complete rewrite with new screens and features
- `public/api/inventories.php` - Added worker/company endpoints

**No Changes Needed**:
- `public/api/login.php` - Authentication unchanged
- `public/api/items.php` - Item fetching unchanged
- `public/api/submissions.php` - Submission logic unchanged
- All web pages - Continue to work independently
