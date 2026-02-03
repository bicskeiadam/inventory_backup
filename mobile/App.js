import React, { useState, useRef, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  Alert,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  FlatList,
  Modal,
  RefreshControl,
  Animated,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { CameraView, useCameraPermissions } from 'expo-camera';
import axios from 'axios';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useColorScheme, Switch } from 'react-native';

// ==================== THEME CONFIGURATION ====================
const THEME = {
  light: {
    primary: '#2563eb',    // Indigo 600
    secondary: '#10B981',  // Emerald 500
    danger: '#EF4444',     // Red 500
    background: '#F3F4F6', // Cool Gray 100
    surface: '#FFFFFF',    // White
    text: '#1F2937',       // Gray 800
    textSecondary: '#6B7280', // Gray 500
    border: '#E5E7EB',     // Gray 200
    input: '#FFFFFF',
    status: 'dark',        // Status bar style
  },
  dark: {
    primary: '#2563eb',    // Indigo 500 (lighter for dark mode)
    secondary: '#34D399',  // Emerald 400
    danger: '#F87171',     // Red 400
    background: '#111827', // Gray 900
    surface: '#1F2937',    // Gray 800
    text: '#ffffffff',       // Gray 50
    textSecondary: '#9CA3AF', // Gray 400
    border: '#374151',     // Gray 700
    input: '#374151',
    status: 'light',       // Status bar style
  }
};

// ⚠️ Change this to your server URL
// For Android Emulator use: http://10.0.2.2/inventory/public/api
// For real device use your PC's IP address (find with: ipconfig in cmd, look for IPv4)
const API_URL = 'https://lapmesterek.stud.vts.su.ac.rs/inventory_backup/public/api';

// Configure axios defaults
axios.defaults.timeout = 10000; // 10 seconds timeout
axios.defaults.headers.post['Content-Type'] = 'application/json';

const CustomAlert = ({ visible, title, message, buttons, theme, onClose }) => {
  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onClose}
    >
      <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'center', alignItems: 'center', padding: 24 }}>
        <View style={{ backgroundColor: theme.surface, borderRadius: 20, padding: 24, width: '100%', maxWidth: 400, shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.25, shadowRadius: 10, elevation: 10 }}>
          <Text style={{ fontSize: 20, fontWeight: 'bold', color: theme.text, marginBottom: 8, textAlign: 'center' }}>{title}</Text>
          <Text style={{ fontSize: 16, color: theme.textSecondary, marginBottom: 24, textAlign: 'center', lineHeight: 22 }}>{message}</Text>
          <View>
            {buttons.map((btn, index) => (
              <TouchableOpacity
                key={index}
                style={{
                  backgroundColor: btn.style === 'destructive' ? theme.danger : btn.style === 'cancel' ? theme.background : theme.primary,
                  padding: 16,
                  borderRadius: 12,
                  alignItems: 'center',
                  borderWidth: btn.style === 'cancel' ? 1 : 0,
                  borderColor: theme.border,
                  marginBottom: index === buttons.length - 1 ? 0 : 12
                }}
                onPress={() => {
                  // Clean up state first, then execute action
                  onClose();
                  // Give modal time to close on Android to avoid conflict before running action?
                  // No, usually best to run action. But if action opens another modal, we might have issue.
                  // Let's run action immediately.
                  if (btn.onPress) {
                    // Wrap in setTimeout to allow modal to close first if needed?
                    // Standard practice is fine.
                    btn.onPress();
                  }
                }}
              >
                <Text style={{
                  color: btn.style === 'cancel' ? theme.text : '#fff',
                  fontWeight: '600',
                  fontSize: 16
                }}>{btn.text}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      </View>
    </Modal>
  );
};

// Animated Screen Wrapper for smooth transitions
const AnimatedScreen = ({ children, style }) => {
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(20)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 250,
        useNativeDriver: true,
      }),
      Animated.timing(slideAnim, {
        toValue: 0,
        duration: 250,
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

  return (
    <Animated.View
      style={[
        { flex: 1, opacity: fadeAnim, transform: [{ translateY: slideAnim }] },
        style,
      ]}
    >
      {children}
    </Animated.View>
  );
};

export default function App() {
  // Auth state
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [token, setToken] = useState(null);
  const [user, setUser] = useState(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  // Theme state
  const systemColorScheme = useColorScheme();
  const [isDarkMode, setIsDarkMode] = useState(false);
  const theme = isDarkMode ? THEME.dark : THEME.light;
  const styles = React.useMemo(() => getThemeStyles(theme), [theme]);

  // Navigation state
  const [currentScreen, setCurrentScreen] = useState('inventories'); // inventories, profile, workers, submissions

  // Inventory state
  const [inventories, setInventories] = useState([]);
  const [selectedInventory, setSelectedInventory] = useState(null);
  const [items, setItems] = useState([]);
  const [recordedItems, setRecordedItems] = useState([]);

  // Modal state
  const [showItemsModal, setShowItemsModal] = useState(false);
  const [showScanner, setShowScanner] = useState(false);
  const [scanned, setScanned] = useState(false);
  const [manualId, setManualId] = useState('');
  const [permission, requestPermission] = useCameraPermissions();

  // Worker/Company state
  const [assignedCompany, setAssignedCompany] = useState(null);
  const [freeWorkers, setFreeWorkers] = useState([]);
  const [assignedWorkers, setAssignedWorkers] = useState([]);
  const [companies, setCompanies] = useState([]);
  const [selectedCompanyId, setSelectedCompanyId] = useState(null);
  const [mySubmissions, setMySubmissions] = useState([]);
  const [employerSubmissions, setEmployerSubmissions] = useState([]); // Submissions for employer review
  const [expandedSubmissionId, setExpandedSubmissionId] = useState(null); // For viewing submission details
  const [reviewComment, setReviewComment] = useState(''); // Comment for employer review
  const [showCompletedSubmissions, setShowCompletedSubmissions] = useState(false); // Toggle completed submissions visibility

  // Offline mode state
  const [isOnline, setIsOnline] = useState(true);

  // Custom Alert State
  const [alertConfig, setAlertConfig] = useState({ visible: false, title: '', message: '', buttons: [] });

  const showAlert = (title, message, buttons = [{ text: 'OK', style: 'default' }]) => {
    setAlertConfig({ visible: true, title, message, buttons });
  };

  const closeAlert = () => {
    setAlertConfig((prev) => ({ ...prev, visible: false }));
  };
  const [pendingSubmissions, setPendingSubmissions] = useState([]);

  // Time tracking state
  const [sessionStartTime, setSessionStartTime] = useState(null);
  const [activityLog, setActivityLog] = useState([]);

  // ==================== OFFLINE & STORAGE ====================

  useEffect(() => {
    loadOfflineData();
    loadActivityLog();
    loadThemePreference();
    checkOnlineStatus();

    // Check online status every 30 seconds
    const intervalId = setInterval(() => {
      if (isLoggedIn) {
        checkOnlineStatus();
      }
    }, 30000);

    // Start session timer
    if (isLoggedIn && !sessionStartTime) {
      const startTime = new Date().toISOString();
      setSessionStartTime(startTime);
      logActivity('SESSION_START', 'Bejelentkezés');
    }

    // Cleanup interval on unmount
    return () => clearInterval(intervalId);
  }, [isLoggedIn]);

  const loadOfflineData = async () => {
    try {
      const storedRecorded = await AsyncStorage.getItem('recordedItems');
      const storedPending = await AsyncStorage.getItem('pendingSubmissions');

      if (storedRecorded) {
        setRecordedItems(JSON.parse(storedRecorded));
      }

      if (storedPending) {
        setPendingSubmissions(JSON.parse(storedPending));
      }
    } catch (error) {
      console.log('Error loading offline data:', error);
    }
  };

  const saveOfflineData = async () => {
    try {
      await AsyncStorage.setItem('recordedItems', JSON.stringify(recordedItems));
      await AsyncStorage.setItem('pendingSubmissions', JSON.stringify(pendingSubmissions));
    } catch (error) {
      console.log('Error saving offline data:', error);
    }
  };

  const loadActivityLog = async () => {
    try {
      const storedLog = await AsyncStorage.getItem('activityLog');
      if (storedLog) {
        setActivityLog(JSON.parse(storedLog));
      }
    } catch (error) {
      console.log('Error loading activity log:', error);
    }
  };

  const loadThemePreference = async () => {
    try {
      const storedTheme = await AsyncStorage.getItem('themePreference');
      if (storedTheme) {
        setIsDarkMode(storedTheme === 'dark');
      } else {
        // Default to system preference if no stored preference
        setIsDarkMode(systemColorScheme === 'dark');
      }
    } catch (error) {
      console.log('Error loading theme preference:', error);
    }
  };

  const toggleTheme = async (value) => {
    setIsDarkMode(value);
    try {
      await AsyncStorage.setItem('themePreference', value ? 'dark' : 'light');
    } catch (error) {
      console.log('Error saving theme preference:', error);
    }
  };

  const logActivity = async (type, description, metadata = {}) => {
    const entry = {
      type,
      description,
      timestamp: new Date().toISOString(),
      ...metadata
    };

    const newLog = [...activityLog, entry];
    setActivityLog(newLog);

    try {
      await AsyncStorage.setItem('activityLog', JSON.stringify(newLog));
    } catch (error) {
      console.log('Error saving activity log:', error);
    }
  };

  const checkOnlineStatus = async () => {
    try {
      // Simple ping to check if server is reachable
      const response = await axios.get(`${API_URL}/inventories.php?ping=1`, {
        timeout: 3000,
        validateStatus: () => true // Accept any status code
      });

      // Consider online if we get ANY response from server
      setIsOnline(true);

      // Try to sync pending submissions if online
      if (pendingSubmissions.length > 0) {
        syncPendingSubmissions();
      }
    } catch (error) {
      console.log('Connection check failed:', error.message);
      setIsOnline(false);
    }
  };

  const syncPendingSubmissions = async () => {
    if (pendingSubmissions.length === 0) return;

    console.log(`Syncing ${pendingSubmissions.length} pending submissions...`);

    for (const submission of pendingSubmissions) {
      try {
        await axios.post(
          `${API_URL}/submissions.php`,
          submission,
          {
            headers: { Authorization: `Bearer ${token}` },
            timeout: 15000,
          }
        );

        // Remove from pending list
        setPendingSubmissions(prev => prev.filter(s => s !== submission));
      } catch (error) {
        console.log('Sync failed for submission:', error.message);
        break; // Stop syncing if one fails
      }
    }

    // Update storage
    await AsyncStorage.setItem('pendingSubmissions', JSON.stringify(pendingSubmissions));

    if (pendingSubmissions.length === 0) {
      showAlert('Szinkronizálva ✅', 'Minden offline adat feltöltve!');
    }
  };

  useEffect(() => {
    saveOfflineData();
  }, [recordedItems, pendingSubmissions]);

  // ==================== API CALLS ====================

  const handleLogin = async () => {
    if (!email.trim() || !password.trim()) {
      showAlert('Hiba', 'Add meg az email címet és jelszót!');
      return;
    }

    setLoading(true);
    try {
      console.log('Attempting login to:', `${API_URL}/login.php`);
      const response = await axios.post(
        `${API_URL}/login.php`,
        {
          email: email.trim(),
          password: password,
        },
        {
          timeout: 10000,
          headers: {
            'Content-Type': 'application/json',
          },
        }
      );

      console.log('Login response:', response.data);
      console.log('User role:', response.data.user?.role);
      console.log('User company_id:', response.data.user?.company_id);

      if (response.data.token) {
        setToken(response.data.token);
        setUser(response.data.user);
        setIsLoggedIn(true);
        setIsOnline(true); // Set online immediately after successful login

        // Fetch additional data based on role
        if (response.data.user.role === 'worker') {
          // Workers use their assigned company_id
          await fetchInventories(response.data.token, response.data.user.company_id, response.data.user.role);
          fetchAssignedCompany(response.data.token);
          fetchMySubmissions(response.data.token);
        } else {
          // Employers/Admins: fetch companies first, then use first company for inventories
          const companies = await fetchCompanies(response.data.token);
          if (companies && companies.length > 0) {
            console.log('Using company for employer:', companies[0].id, companies[0].name);
            await fetchInventories(response.data.token, companies[0].id, response.data.user.role);
            fetchEmployerSubmissions(companies[0].id, response.data.token);
          }
          if (response.data.user.role === 'employer' || response.data.user.role === 'admin') {
            fetchWorkers(response.data.token);
          }
        }
      } else {
        showAlert('Hiba', 'Hibás válasz a szervertől');
      }
    } catch (error) {
      console.log('Login error:', error.message);
      console.log('Error details:', error.response?.data);

      let errorMessage = 'Hibás email vagy jelszó!';

      if (error.code === 'ECONNABORTED') {
        errorMessage = 'Időtúllépés! Ellenőrizd a szerver elérhetőségét.';
      } else if (error.message === 'Network Error') {
        errorMessage = 'Hálózati hiba! \n\nEllenőrizd:\n1. XAMPP fut-e\n2. API_URL helyes-e az App.js-ben\n3. Telefonod és a PC ugyanazon a hálózaton van-e';
      } else if (error.response?.status === 401) {
        errorMessage = 'Hibás email vagy jelszó!';
      } else if (error.response?.status === 422) {
        errorMessage = 'Hiányzó adatok!';
      }

      showAlert('Bejelentkezési hiba', errorMessage);
    }
    setLoading(false);
  };

  const handleLogout = () => {
    showAlert('Kilépés', 'Biztosan ki szeretnél lépni?', [
      {
        text: 'Kilépés',
        style: 'destructive',
        onPress: async () => {
          // Log session end
          if (sessionStartTime) {
            const duration = new Date() - new Date(sessionStartTime);
            const minutes = Math.floor(duration / 60000);
            logActivity('SESSION_END', `Munkamenet befejezve (${minutes} perc)`);
          }

          setIsLoggedIn(false);
          setToken(null);
          setUser(null);
          setEmail('');
          setPassword('');
          setInventories([]);
          setSelectedInventory(null);
          setItems([]);
          setRecordedItems([]);
          setSessionStartTime(null);
        },
      },
      { text: 'Mégse', style: 'cancel' },
    ]);
  };

  const fetchInventories = async (authToken, companyId, userRole) => {
    try {
      const cid = companyId || user?.company_id;
      if (!cid) {
        console.log('No company_id available');
        return;
      }
      const response = await axios.get(
        `${API_URL}/inventories.php?company_id=${cid}`,
        {
          headers: { Authorization: `Bearer ${authToken || token}` },
          timeout: 10000,
        }
      );
      let allInventories = response.data.inventories || [];
      console.log('All inventories from API:', allInventories.map(i => ({ id: i.id, name: i.name, status: i.status })));

      // Filter only active inventories for workers
      const role = userRole || user?.role;
      console.log('Filtering for role:', role);
      if (role === 'worker') {
        allInventories = allInventories.filter(inv => inv.status === 'active');
        console.log('Active inventories after filter:', allInventories.length);
      }

      setInventories(allInventories);
    } catch (error) {
      console.log('Fetch inventories error:', error.message);
      if (error.code === 'ECONNABORTED' || error.message === 'Network Error') {
        showAlert('Hálózati hiba', 'Nem sikerült betölteni a leltárakat');
      }
    }
  };

  const fetchAssignedCompany = async (authToken) => {
    if (user?.role !== 'worker') return;

    try {
      const response = await axios.get(
        `${API_URL}/inventories.php?get_worker_company=1`,
        {
          headers: { Authorization: `Bearer ${authToken || token}` },
          timeout: 10000,
        }
      );
      if (response.data.company) {
        setAssignedCompany(response.data.company);
      }
    } catch (error) {
      console.log('Fetch assigned company error:', error.message);
    }
  };

  const fetchCompanies = async (authToken) => {
    try {
      const response = await axios.get(
        `${API_URL}/inventories.php?get_companies=1`,
        {
          headers: { Authorization: `Bearer ${authToken || token}` },
          timeout: 10000,
        }
      );
      const companiesList = response.data.companies || [];
      setCompanies(companiesList);
      // Auto-select first company for employer
      if (companiesList.length > 0) {
        setSelectedCompanyId(companiesList[0].id);
      }
      return companiesList;
    } catch (error) {
      console.log('Fetch companies error:', error.message);
      return [];
    }
  };

  const fetchWorkers = async (authToken) => {
    try {
      const response = await axios.get(
        `${API_URL}/inventories.php?get_workers=1`,
        {
          headers: { Authorization: `Bearer ${authToken || token}` },
          timeout: 10000,
        }
      );
      setFreeWorkers(response.data.free_workers || []);
      setAssignedWorkers(response.data.assigned_workers || []);
    } catch (error) {
      console.log('Fetch workers error:', error.message);
    }
  };

  const fetchMySubmissions = async (authToken) => {
    try {
      const response = await axios.get(
        `${API_URL}/inventories.php?get_my_submissions=1`,
        {
          headers: { Authorization: `Bearer ${authToken || token}` },
          timeout: 10000,
        }
      );
      setMySubmissions(response.data.submissions || []);
    } catch (error) {
      console.log('Fetch my submissions error:', error.message);
    }
  };

  // Fetch submissions for employer's company
  const fetchEmployerSubmissions = async (companyId, authToken) => {
    console.log('fetchEmployerSubmissions called with companyId:', companyId);
    if (!companyId) {
      console.log('No companyId provided, skipping fetch');
      return;
    }
    try {
      const url = `${API_URL}/inventories.php?get_company_submissions=1&company_id=${companyId}`;
      console.log('Fetching submissions from:', url);
      const response = await axios.get(
        url,
        {
          headers: { Authorization: `Bearer ${authToken || token}` },
          timeout: 10000,
        }
      );
      console.log('Employer submissions response:', response.data);
      setEmployerSubmissions(response.data.submissions || []);
    } catch (error) {
      console.log('Fetch employer submissions error:', error.message);
      console.log('Error response:', error.response?.data);
    }
  };

  // Review a submission (approve/reject)
  const reviewSubmission = async (submissionId, status, message = '') => {
    try {
      setLoading(true);
      await axios.post(
        `${API_URL}/inventories.php`,
        {
          action: 'review_submission',
          submission_id: submissionId,
          status: status, // 'approved' or 'rejected'
          message: message, // optional comment from employer
        },
        {
          headers: { Authorization: `Bearer ${token}` },
          timeout: 10000,
        }
      );
      showAlert('Siker!', status === 'approved' ? 'Beküldés elfogadva!' : 'Beküldés elutasítva.');
      // Reset state
      setExpandedSubmissionId(null);
      setReviewComment('');
      // Refresh the list
      if (selectedCompanyId || companies.length > 0) {
        await fetchEmployerSubmissions(selectedCompanyId || companies[0]?.id);
      }
    } catch (error) {
      console.log('Review submission error:', error.message);
      showAlert('Hiba', 'Nem sikerült feldolgozni a döntést.');
    } finally {
      setLoading(false);
    }
  };

  const fetchItems = async (inventoryId) => {
    try {
      const response = await axios.get(
        `${API_URL}/items.php?inventory_id=${inventoryId}`,
        {
          headers: { Authorization: `Bearer ${token}` },
          timeout: 10000,
        }
      );
      setItems(response.data.items || []);
    } catch (error) {
      console.log('Fetch items error:', error.message);
      setItems([]);
      if (error.code === 'ECONNABORTED' || error.message === 'Network Error') {
        showAlert('Hálózati hiba', 'Nem sikerült betölteni az eszközöket');
      }
    }
  };

  const selectInventory = (inventory) => {
    setSelectedInventory(inventory);
    setRecordedItems([]);
    fetchItems(inventory.id);
    logActivity('INVENTORY_SELECTED', `Leltár kiválasztva: ${inventory.name}`, { inventory_id: inventory.id });
  };

  const submitInventory = async () => {
    if (recordedItems.length === 0) {
      showAlert('Hiba', 'Nincs rögzített eszköz!');
      return;
    }

    setLoading(true);

    const payload = {
      items: recordedItems.map((item) => ({
        item_id: item.id,
        is_present: item.isPresent ? 1 : 0,
        note: item.note || (item.isPresent ? 'Megtalálva' : 'Hiányzik'),
        photo: item.photo || null,
      })),
    };

    const submissionData = {
      inventory_id: selectedInventory.id,
      payload
    };

    try {
      if (isOnline) {
        await axios.post(
          `${API_URL}/submissions.php`,
          submissionData,
          {
            headers: { Authorization: `Bearer ${token}` },
            timeout: 15000,
          }
        );

        showAlert('Beküldve! ⏳', 'A leltár adatok elküldésre kerültek. A munkáltatód hamarosan áttekinti és jóváhagyja.');
        logActivity('SUBMISSION_SUCCESS', `${recordedItems.length} eszköz elküldve`);
        setRecordedItems([]);
        // Navigate back to inventory list after submission
        setSelectedInventory(null);
        fetchMySubmissions();  // Refresh submissions list
      } else {
        // Save to pending submissions for later sync
        setPendingSubmissions([...pendingSubmissions, submissionData]);
        showAlert('Offline Mód 📴', 'Az adatok mentve lettek és automatikusan feltöltésre kerülnek, amikor újra online lesz a készülék.');
        logActivity('SUBMISSION_OFFLINE', `${recordedItems.length} eszköz offline mentve`);
        setRecordedItems([]);
      }
    } catch (error) {
      console.log('Submit error:', error.message);
      console.log('Submit error response:', error.response?.data);

      // If network error, save offline
      if (error.code === 'ECONNABORTED' || error.message === 'Network Error') {
        setPendingSubmissions([...pendingSubmissions, submissionData]);
        showAlert('Offline Mód 📴', 'Hálózati hiba! Az adatok mentve lettek offline módban.');
        logActivity('SUBMISSION_OFFLINE', `${recordedItems.length} eszköz offline mentve (hálózati hiba)`);
        setRecordedItems([]);
      } else {
        showAlert('Hiba', 'Nem sikerült elküldeni az adatokat: ' + error.message);
      }
    }
    setLoading(false);
  };

  // ==================== ITEM HANDLING ====================

  const recordItem = (item, isPresent, note = null, photo = null) => {
    const exists = recordedItems.find((r) => r.id === item.id);
    if (exists) {
      showAlert('Figyelem', 'Ez az eszköz már rögzítve van!');
      return;
    }

    const timestamp = new Date().toISOString();
    setRecordedItems([
      ...recordedItems,
      {
        ...item,
        isPresent,
        note: note || (isPresent ? 'Megtalálva' : 'Hiányzik'),
        photo,
        recordedAt: timestamp,
      },
    ]);

    logActivity(
      isPresent ? 'ITEM_FOUND' : 'ITEM_MISSING',
      `${item.name} - ${isPresent ? 'Megtalálva' : 'Hiányzik'}`,
      { item_id: item.id, inventory_id: selectedInventory?.id }
    );
  };

  const removeRecordedItem = (itemId) => {
    setRecordedItems(recordedItems.filter((r) => r.id !== itemId));
  };

  const handleManualEntry = () => {
    const id = parseInt(manualId);
    if (!id) {
      showAlert('Hiba', 'Adj meg egy érvényes ID-t!');
      return;
    }

    const item = items.find((i) => i.id === id);
    if (!item) {
      showAlert('Hiba', 'Nincs ilyen eszköz a leltárban!');
      return;
    }

    showAlert(item.name, 'Mi az eszköz állapota?', [
      { text: '✅ Megvan', onPress: () => recordItem(item, true) },
      { text: '❌ Hiányzik', onPress: () => recordItem(item, false) },
      { text: 'Mégse', style: 'cancel' },
    ]);

    setManualId('');
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchInventories(token, user?.company_id);
    setRefreshing(false);
  };

  // ==================== QR SCANNER ====================

  const openScanner = async () => {
    if (!permission?.granted) {
      const result = await requestPermission();
      if (!result.granted) {
        showAlert('Hiba', 'Kamera engedély szükséges a QR kód olvasáshoz!');
        return;
      }
    }
    setScanned(false);
    setShowScanner(true);
  };

  const handleBarcodeScanned = ({ type, data }) => {
    if (scanned) return;
    setScanned(true);

    console.log('QR Code scanned:', data);

    let item = null;

    // Try to parse as JSON first (old format: {"room":3,"name":"Kabel","ts":1768422403})
    try {
      const parsed = JSON.parse(data);
      if (parsed.room && parsed.name) {
        // Find item by room_id and name
        item = items.find((i) => i.room_id == parsed.room && i.name === parsed.name);
        if (!item) {
          showAlert('Hiba', `Eszköz "${parsed.name}" nem található ebben a leltárban!`, [
            { text: 'Újra', onPress: () => setScanned(false) },
            { text: 'Bezárás', onPress: () => setShowScanner(false) },
          ]);
          return;
        }
      }
    } catch (e) {
      // Not JSON, try other formats
      let itemId = null;

      // Check if it's just a number
      if (/^\d+$/.test(data)) {
        itemId = parseInt(data);
      } else if (data.includes('item_id=')) {
        // Our format: item_id=123
        const match = data.match(/item_id=(\d+)/);
        if (match) {
          itemId = parseInt(match[1]);
        }
      } else {
        // Try to extract ID from any other format
        const match = data.match(/id[=:]?\s*(\d+)/i) || data.match(/(\d+)/);
        if (match) {
          itemId = parseInt(match[1]);
        }
      }

      if (!itemId) {
        showAlert('Hiba', `Nem sikerült azonosítót kiolvasni: ${data}`, [
          { text: 'Újra', onPress: () => setScanned(false) },
          { text: 'Bezárás', onPress: () => setShowScanner(false) },
        ]);
        return;
      }

      item = items.find((i) => i.id === itemId);
      if (!item) {
        showAlert('Hiba', `Nincs ilyen eszköz (ID: ${itemId}) a leltárban!`, [
          { text: 'Újra', onPress: () => setScanned(false) },
          { text: 'Bezárás', onPress: () => setShowScanner(false) },
        ]);
        return;
      }
    }

    if (!item) {
      showAlert('Hiba', `Nem sikerült azonosítani az eszközt!`, [
        { text: 'Újra', onPress: () => setScanned(false) },
        { text: 'Bezárás', onPress: () => setShowScanner(false) },
      ]);
      return;
    }

    const recorded = recordedItems.find((r) => r.id === item.id);
    if (recorded) {
      showAlert('Figyelem', 'Ez az eszköz már rögzítve van!', [
        { text: 'Újra', onPress: () => setScanned(false) },
        { text: 'Bezárás', onPress: () => setShowScanner(false) },
      ]);
      return;
    }

    // Close scanner and show item dialog
    setShowScanner(false);

    showAlert(item.name, `ID: ${item.id}\n\nMi az eszköz állapota?`, [
      { text: 'Mégse', style: 'cancel' },
      { text: '❌ Hiányzik', onPress: () => recordItem(item, false) },
      { text: '✅ Megvan', onPress: () => recordItem(item, true) },
    ]);
  };

  // ==================== WORKER ASSIGNMENT (FOR EMPLOYERS) ====================

  const assignWorker = async (workerId, companyId) => {
    try {
      const response = await axios.post(
        `${API_URL}/inventories.php`,
        {
          action: 'assign_worker',
          worker_id: workerId,
          company_id: companyId,
        },
        {
          headers: { Authorization: `Bearer ${token}` },
          timeout: 10000,
        }
      );

      if (response.data.success) {
        showAlert('Siker', 'Munkavállaló hozzárendelve!');
        fetchWorkers();
      } else {
        showAlert('Hiba', response.data.message || 'Hozzárendelés sikertelen');
      }
    } catch (error) {
      console.log('Assign worker error:', error.message);
      showAlert('Hiba', 'Nem sikerült hozzárendelni a munkavállalót');
    }
  };

  const removeWorkerAssignment = async (workerId) => {
    try {
      const response = await axios.post(
        `${API_URL}/inventories.php`,
        {
          action: 'remove_worker',
          worker_id: workerId,
        },
        {
          headers: { Authorization: `Bearer ${token}` },
          timeout: 10000,
        }
      );

      if (response.data.success) {
        showAlert('Siker', 'Hozzárendelés törölve!');
        fetchWorkers();
      } else {
        showAlert('Hiba', response.data.message || 'Törlés sikertelen');
      }
    } catch (error) {
      console.log('Remove worker error:', error.message);
      showAlert('Hiba', 'Nem sikerült törölni a hozzárendelést');
    }
  };

  // ==================== CONNECTION TEST ====================

  const testConnection = async () => {
    setLoading(true);
    try {
      console.log('Testing connection to:', `${API_URL}/login.php`);
      const response = await axios.get(`${API_URL}/login.php`, {
        timeout: 5000,
        validateStatus: () => true, // Accept any status
      });
      showAlert(
        'Kapcsolat OK! ✅',
        `Szerver elérhető!\nÁllapot: ${response.status}\n\nMost próbálj meg bejelentkezni.`
      );
    } catch (error) {
      console.log('Connection test error:', error.message);
      showAlert(
        'Kapcsolat HIBA! ❌',
        `Nem sikerült elérni a szervert!\n\nURL: ${API_URL}\n\nHiba: ${error.message}\n\nEllenőrizd:\n1. XAMPP fut-e\n2. Apache elindult-e\n3. API_URL helyes-e`
      );
    }
    setLoading(false);
  };

  // ==================== SCREENS ====================

  // Screen rendering is handled in the main return block at the end of this function
  // ==================== LOGIN SCREEN ====================

  function renderLoginScreen() {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AnimatedScreen>
          <View style={styles.loginWrapper}>
            <Ionicons name="cube" size={80} color="#fff" style={{ marginBottom: 16 }} />
            <Text style={styles.title}>Leltár App</Text>
            <Text style={styles.subtitle}>Jelentkezz be a folytatáshoz</Text>

            <View style={styles.form}>
              <View style={styles.inputContainer}>
                <Ionicons name="mail-outline" size={20} color={theme.textSecondary} style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="Email cím"
                  placeholderTextColor={theme.textSecondary}
                  value={email}
                  onChangeText={setEmail}
                  autoCapitalize="none"
                  keyboardType="email-address"
                  autoCorrect={false}
                />
              </View>

              <View style={styles.inputContainer}>
                <Ionicons name="lock-closed-outline" size={20} color={theme.textSecondary} style={styles.inputIcon} />
                <TextInput
                  style={styles.input}
                  placeholder="Jelszó"
                  placeholderTextColor={theme.textSecondary}
                  value={password}
                  onChangeText={setPassword}
                  secureTextEntry
                />
              </View>

              <TouchableOpacity
                style={[styles.btn, styles.btnPrimary, loading && styles.btnDisabled]}
                onPress={handleLogin}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <Text style={styles.btnText}>Bejelentkezés</Text>
                )}
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.btn, styles.btnOutline, { marginTop: 10 }]}
                onPress={testConnection}
                disabled={loading}
              >
                <Text style={styles.btnOutlineText}>🔍 Kapcsolat tesztelése</Text>
              </TouchableOpacity>

              <Text style={styles.apiUrlText}>API: {API_URL}</Text>
            </View>
          </View>
        </AnimatedScreen>
      </SafeAreaView>
    );
  }

  // ==================== PROFILE SCREEN ====================

  function renderProfileScreen() {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AnimatedScreen>
          <View style={styles.header}>
            <TouchableOpacity onPress={() => setCurrentScreen('profile')} style={{ padding: 8 }}>
              <Ionicons name="arrow-back" size={24} color="#fff" />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>Profil</Text>
            <TouchableOpacity onPress={handleLogout}>
              <Text style={styles.headerBtn}>Kilépés</Text>
            </TouchableOpacity>
          </View>

          <ScrollView style={styles.content}>
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Felhasználói Adatok</Text>

              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Név:</Text>
                <Text style={styles.infoValue}>{user?.name || 'N/A'}</Text>
              </View>

              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Email:</Text>
                <Text style={styles.infoValue}>{user?.email || 'N/A'}</Text>
              </View>

              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Szerepkör:</Text>
                <Text style={styles.infoValue}>
                  {user?.role === 'worker' && '👷 Munkavállaló'}
                  {user?.role === 'employer' && '👔 Munkáltató'}
                  {user?.role === 'admin' && '⚙️ Adminisztrátor'}
                </Text>
              </View>

              {user?.role === 'worker' && assignedCompany && (
                <>
                  <View style={styles.divider} />
                  <Text style={styles.sectionTitle}>Hozzárendelt Cég</Text>
                  <View style={styles.infoRow}>
                    <Text style={styles.infoLabel}>Cég:</Text>
                    <Text style={styles.infoValue}>{assignedCompany.name}</Text>
                  </View>
                </>
              )}
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Beállítások</Text>
              <View style={styles.infoRow}>
                <View style={[styles.row, { justifyContent: 'space-between' }]}>
                  <Text style={styles.infoLabel}>Sötét Mód</Text>
                  <Switch
                    value={isDarkMode}
                    onValueChange={toggleTheme}
                    trackColor={{ false: '#767577', true: THEME.light.primary }}
                    thumbColor={isDarkMode ? '#fff' : '#f4f3f4'}
                  />
                </View>
              </View>
            </View>

            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Munkamenet Info</Text>
              {sessionStartTime && (
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Munkamenet kezdete:</Text>
                  <Text style={styles.infoValue}>
                    {new Date(sessionStartTime).toLocaleString('hu-HU')}
                  </Text>
                </View>
              )}
              <View style={styles.infoRow}>
                <Text style={styles.infoLabel}>Rögzített tevékenységek:</Text>
                <Text style={styles.infoValue}>{activityLog.length}</Text>
              </View>
              {pendingSubmissions.length > 0 && (
                <View style={styles.infoRow}>
                  <Text style={styles.infoLabel}>Várakozó küldések:</Text>
                  <Text style={styles.infoValue}>{pendingSubmissions.length}</Text>
                </View>
              )}
            </View>
          </ScrollView>

          <View style={styles.bottomNav}>
            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('inventories')}
            >
              <Ionicons name="clipboard-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Leltárak</Text>
            </TouchableOpacity>

            {user?.role === 'worker' && (
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => { setCurrentScreen('submissions'); fetchMySubmissions(); }}
              >
                <Ionicons name="send-outline" size={24} color={theme.textSecondary} />
                <Text style={styles.navBtnText}>Beküldések</Text>
              </TouchableOpacity>
            )}

            {(user?.role === 'employer' || user?.role === 'admin') && (
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => { setCurrentScreen('submissions'); fetchEmployerSubmissions(selectedCompanyId || companies[0]?.id); }}
              >
                <Ionicons name="document-text-outline" size={24} color={theme.textSecondary} />
                <Text style={styles.navBtnText}>Beküldések</Text>
              </TouchableOpacity>
            )}

            {(user?.role === 'employer' || user?.role === 'admin') && (
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => setCurrentScreen('workers')}
              >
                <Ionicons name="people-outline" size={24} color={theme.textSecondary} />
                <Text style={styles.navBtnText}>Munkások</Text>
              </TouchableOpacity>
            )}

            <TouchableOpacity
              style={[styles.navBtn, styles.navBtnActive]}
              onPress={() => setCurrentScreen('profile')}
            >
              <Ionicons name="person" size={24} color={theme.primary} />
              <Text style={[styles.navBtnText, styles.navBtnActiveText]}>Profil</Text>
            </TouchableOpacity>
          </View>
        </AnimatedScreen>
      </SafeAreaView>
    );
  }

  // ==================== WORKERS SCREEN (EMPLOYER/ADMIN) ====================

  function renderWorkersScreen() {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AnimatedScreen>
          <View style={styles.header}>
            <TouchableOpacity onPress={() => setCurrentScreen('inventories')} style={{ padding: 8 }}>
              <Ionicons name="arrow-back" size={24} color="#fff" />
            </TouchableOpacity>
            <Text style={styles.headerTitle}>Munkás Kezelés</Text>
            <TouchableOpacity onPress={handleLogout}>
              <Text style={styles.headerBtn}>Kilépés</Text>
            </TouchableOpacity>
          </View>

          <ScrollView style={styles.content}>
            {/* Free Workers */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Szabad Munkások ({freeWorkers.length})</Text>

              {freeWorkers.length === 0 ? (
                <Text style={styles.muted}>Nincs szabad munkavállaló</Text>
              ) : (
                freeWorkers.map((worker) => (
                  <View key={worker.id} style={styles.workerItem}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.workerName}>{worker.name}</Text>
                      <Text style={styles.workerMeta}>{worker.email}</Text>
                    </View>
                    <TouchableOpacity
                      style={[styles.btn, styles.btnSuccess, { paddingVertical: 8, paddingHorizontal: 12 }]}
                      onPress={() => {
                        if (companies.length === 0) {
                          showAlert('Hiba', 'Nincs elérhető cég!');
                          return;
                        }

                        // Show company picker
                        showAlert(
                          'Cég kiválasztása',
                          `Melyik céghez rendeled ${worker.name} munkavállalót?`,
                          companies.map(company => ({
                            text: company.name,
                            onPress: () => assignWorker(worker.id, company.id)
                          })).concat([{ text: 'Mégse', style: 'cancel' }])
                        );
                      }}
                    >
                      <Text style={styles.btnText}>Hozzárendel</Text>
                    </TouchableOpacity>
                  </View>
                ))
              )}
            </View>

            {/* Assigned Workers */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Hozzárendelt Munkások</Text>

              {assignedWorkers.length === 0 ? (
                <Text style={styles.muted}>Nincs hozzárendelt munkavállaló</Text>
              ) : (
                Object.entries(
                  assignedWorkers.reduce((acc, worker) => {
                    const companyName = worker.company_name || 'Nincs cég';
                    if (!acc[companyName]) acc[companyName] = [];
                    acc[companyName].push(worker);
                    return acc;
                  }, {})
                ).map(([companyName, workers]) => (
                  <View key={companyName} style={{ marginBottom: 16 }}>
                    <Text style={styles.companyTitle}>🏢 {companyName}</Text>
                    {workers.map((worker) => (
                      <View key={worker.id} style={styles.workerItem}>
                        <View style={{ flex: 1 }}>
                          <Text style={styles.workerName}>{worker.name}</Text>
                          <Text style={styles.workerMeta}>{worker.email}</Text>
                        </View>
                        <TouchableOpacity
                          style={[styles.btn, styles.btnDanger, { paddingVertical: 8, paddingHorizontal: 12 }]}
                          onPress={() => {
                            showAlert(
                              'Megerősítés',
                              `Biztosan eltávolítod ${worker.name} hozzárendelését?`,
                              [
                                { text: 'Mégse', style: 'cancel' },
                                { text: 'Törlés', style: 'destructive', onPress: () => removeWorkerAssignment(worker.id) }
                              ]
                            );
                          }}
                        >
                          <Text style={styles.btnText}>Eltávolít</Text>
                        </TouchableOpacity>
                      </View>
                    ))}
                  </View>
                ))
              )}
            </View>
          </ScrollView>

          <View style={styles.bottomNav}>
            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('inventories')}
            >
              <Ionicons name="clipboard-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Leltárak</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => { setCurrentScreen('submissions'); fetchEmployerSubmissions(selectedCompanyId || companies[0]?.id); }}
            >
              <Ionicons name="document-text-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Beküldések</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.navBtn, styles.navBtnActive]}
              onPress={() => setCurrentScreen('workers')}
            >
              <Ionicons name="people" size={24} color={theme.primary} />
              <Text style={[styles.navBtnText, styles.navBtnActiveText]}>Munkások</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('profile')}
            >
              <Ionicons name="person-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Profil</Text>
            </TouchableOpacity>
          </View>
        </AnimatedScreen>
      </SafeAreaView>
    );
  }

  // ==================== MY SUBMISSIONS SCREEN (WORKER) ====================

  function renderSubmissionsScreen() {
    const getStatusBadge = (status) => {
      switch (status) {
        case 'approved': return { text: 'Elfogadva', color: theme.secondary, icon: 'checkmark-circle' };
        case 'rejected': return { text: 'Elutasítva', color: theme.danger, icon: 'close-circle' };
        default: return { text: 'Várakozik', color: '#F59E0B', icon: 'time' };
      }
    };

    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AnimatedScreen>
          <View style={styles.header}>
            <View>
              <Text style={styles.headerTitle}>📤 Beküldéseim</Text>
            </View>
            <TouchableOpacity onPress={() => fetchMySubmissions()}>
              <Ionicons name="refresh" size={24} color="#fff" />
            </TouchableOpacity>
          </View>

          <View style={{ flex: 1 }}>
            <ScrollView
              contentContainerStyle={{ padding: 16 }}
              refreshControl={
                <RefreshControl refreshing={refreshing} onRefresh={() => fetchMySubmissions()} />
              }
            >
              {mySubmissions.length === 0 ? (
                <View style={styles.empty}>
                  <Ionicons name="document-text-outline" size={64} color={theme.textSecondary} style={{ marginBottom: 16 }} />
                  <Text style={styles.emptyText}>Még nincs beküldésed</Text>
                  <Text style={{ color: theme.textSecondary, textAlign: 'center', marginTop: 8 }}>
                    A beküldött leltárak itt jelennek meg
                  </Text>
                </View>
              ) : (
                <>
                  {/* Pending Submissions Section */}
                  {(() => {
                    const pendingItems = mySubmissions.filter(s => s.status === 'pending');
                    const completedItems = mySubmissions.filter(s => s.status !== 'pending');

                    const renderSubmissionCard = (item) => {
                      const status = getStatusBadge(item.status);
                      const payload = JSON.parse(item.payload || '{}');
                      const itemCount = payload.items?.length || 0;

                      return (
                        <View key={item.id} style={[styles.card, { borderLeftWidth: 4, borderLeftColor: status.color, marginBottom: 12 }]}>
                          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                            <View style={{ flex: 1 }}>
                              <Text style={styles.cardTitle}>{item.inventory_name || 'Leltár #' + item.inventory_id}</Text>
                              <Text style={styles.cardMeta}>
                                <Ionicons name="calendar-outline" size={12} color={theme.textSecondary} /> {item.created_at}
                              </Text>
                              <Text style={{ fontSize: 13, color: theme.textSecondary, marginTop: 4 }}>
                                {itemCount} rögzített eszköz
                              </Text>
                            </View>
                            <View style={{
                              backgroundColor: status.color + '20',
                              paddingHorizontal: 10,
                              paddingVertical: 6,
                              borderRadius: 8,
                              flexDirection: 'row',
                              alignItems: 'center'
                            }}>
                              <Ionicons name={status.icon} size={14} color={status.color} style={{ marginRight: 4 }} />
                              <Text style={{ color: status.color, fontWeight: '600', fontSize: 12 }}>{status.text}</Text>
                            </View>
                          </View>

                          {/* Employer response/comment */}
                          {item.employer_response && (
                            <View style={{
                              marginTop: 12,
                              padding: 12,
                              backgroundColor: theme.card,
                              borderRadius: 8,
                              borderWidth: 1,
                              borderColor: theme.border
                            }}>
                              <Text style={{ fontSize: 12, color: theme.textSecondary, marginBottom: 4 }}>
                                💬 Munkáltató üzenete:
                              </Text>
                              <Text style={{ fontSize: 14, color: theme.text }}>
                                {item.employer_response}
                              </Text>
                            </View>
                          )}
                        </View>
                      );
                    };

                    return (
                      <>
                        {/* Pending section header */}
                        {pendingItems.length > 0 && (
                          <View style={{ marginBottom: 12 }}>
                            <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 12 }}>
                              <Ionicons name="time" size={20} color="#F59E0B" style={{ marginRight: 8 }} />
                              <Text style={{ fontSize: 16, fontWeight: '600', color: theme.text }}>
                                Várakozik ({pendingItems.length})
                              </Text>
                            </View>
                            {pendingItems.map(renderSubmissionCard)}
                          </View>
                        )}

                        {/* Completed section with toggle */}
                        {completedItems.length > 0 && (
                          <View>
                            <TouchableOpacity
                              onPress={() => setShowCompletedSubmissions(!showCompletedSubmissions)}
                              style={{
                                flexDirection: 'row',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                paddingVertical: 12,
                                paddingHorizontal: 16,
                                backgroundColor: theme.card,
                                borderRadius: 10,
                                marginBottom: 12,
                                borderWidth: 1,
                                borderColor: theme.border,
                              }}
                            >
                              <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                                <Ionicons name="checkmark-done" size={20} color={theme.secondary} style={{ marginRight: 8 }} />
                                <Text style={{ fontSize: 16, fontWeight: '600', color: theme.text }}>
                                  Lezárt beküldések ({completedItems.length})
                                </Text>
                              </View>
                              <Ionicons
                                name={showCompletedSubmissions ? "chevron-up" : "chevron-down"}
                                size={20}
                                color={theme.textSecondary}
                              />
                            </TouchableOpacity>

                            {showCompletedSubmissions && completedItems.map(renderSubmissionCard)}
                          </View>
                        )}
                      </>
                    );
                  })()}
                </>
              )}
            </ScrollView>
          </View>

          <View style={styles.bottomNav}>
            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('inventories')}
            >
              <Ionicons name="clipboard-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Leltárak</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.navBtn, styles.navBtnActive]}
              onPress={() => setCurrentScreen('submissions')}
            >
              <Ionicons name="send" size={24} color={theme.primary} />
              <Text style={[styles.navBtnText, styles.navBtnActiveText]}>Beküldések</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('profile')}
            >
              <Ionicons name="person-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Profil</Text>
            </TouchableOpacity>
          </View>
        </AnimatedScreen>
      </SafeAreaView>
    );
  }

  // ==================== EMPLOYER REVIEW SCREEN ====================

  function renderEmployerReviewScreen() {
    const getStatusBadge = (status) => {
      switch (status) {
        case 'approved': return { text: 'Elfogadva', color: theme.secondary, icon: 'checkmark-circle' };
        case 'rejected': return { text: 'Elutasítva', color: theme.danger, icon: 'close-circle' };
        default: return { text: 'Várakozik', color: '#F59E0B', icon: 'time' };
      }
    };

    const pendingCount = employerSubmissions.filter(s => s.status === 'pending').length;

    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AnimatedScreen>
          <View style={styles.header}>
            <View style={{ flexDirection: 'row', alignItems: 'center', flex: 1, marginRight: 12 }}>
              <Text style={styles.headerTitle}>📋 Beküldések</Text>
              {pendingCount > 0 && (
                <View style={{
                  backgroundColor: '#F59E0B',
                  paddingHorizontal: 10,
                  paddingVertical: 5,
                  borderRadius: 12,
                  marginLeft: 10,
                  flexDirection: 'row',
                  alignItems: 'center',
                }}>
                  <Ionicons name="time" size={12} color="#fff" style={{ marginRight: 4 }} />
                  <Text style={{ color: '#fff', fontSize: 12, fontWeight: 'bold' }}>
                    {pendingCount} új
                  </Text>
                </View>
              )}
            </View>
            <TouchableOpacity onPress={() => fetchEmployerSubmissions(selectedCompanyId || companies[0]?.id)}>
              <Ionicons name="refresh" size={24} color="#fff" />
            </TouchableOpacity>
          </View>

          <View style={{ flex: 1 }}>
            <ScrollView
              contentContainerStyle={{ padding: 16 }}
              refreshControl={
                <RefreshControl refreshing={refreshing} onRefresh={() => fetchEmployerSubmissions(selectedCompanyId || companies[0]?.id)} />
              }
            >
              {employerSubmissions.length === 0 ? (
                <View style={styles.empty}>
                  <Ionicons name="checkmark-done-circle-outline" size={64} color={theme.textSecondary} style={{ marginBottom: 16 }} />
                  <Text style={styles.emptyText}>Nincs beküldés</Text>
                  <Text style={{ color: theme.textSecondary, textAlign: 'center', marginTop: 8 }}>
                    A munkások beküldései itt jelennek meg
                  </Text>
                </View>
              ) : (
                <>
                  {(() => {
                    const pendingItems = employerSubmissions.filter(s => s.status === 'pending');
                    const completedItems = employerSubmissions.filter(s => s.status !== 'pending');

                    const renderEmployerSubmissionCard = (item) => {
                      const status = getStatusBadge(item.status);
                      const payload = JSON.parse(item.payload || '{}');
                      const items = payload.items || [];
                      const itemCount = items.length;
                      const foundCount = items.filter(i => i.is_present === 1).length;
                      const missingCount = items.filter(i => i.is_present === 0).length;
                      const isPending = item.status === 'pending';
                      const isExpanded = expandedSubmissionId === item.id;

                      return (
                        <View key={item.id} style={[styles.card, { borderLeftWidth: 4, borderLeftColor: status.color, marginBottom: 12 }]}>
                          {/* Header - Tappable to expand */}
                          <TouchableOpacity
                            onPress={() => {
                              setExpandedSubmissionId(isExpanded ? null : item.id);
                              setReviewComment('');
                            }}
                            activeOpacity={0.7}
                          >
                            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 12 }}>
                              <View style={{ flex: 1 }}>
                                <Text style={styles.cardTitle}>{item.inventory_name}</Text>
                                <Text style={{ fontSize: 14, color: theme.text, marginTop: 4 }}>
                                  👤 {item.worker_name}
                                </Text>
                                <Text style={styles.cardMeta}>
                                  <Ionicons name="calendar-outline" size={12} color={theme.textSecondary} /> {item.created_at}
                                </Text>
                              </View>
                              <View style={{ alignItems: 'flex-end' }}>
                                <View style={{
                                  backgroundColor: status.color + '20',
                                  paddingHorizontal: 10,
                                  paddingVertical: 6,
                                  borderRadius: 8,
                                  flexDirection: 'row',
                                  alignItems: 'center'
                                }}>
                                  <Ionicons name={status.icon} size={14} color={status.color} style={{ marginRight: 4 }} />
                                  <Text style={{ color: status.color, fontWeight: '600', fontSize: 12 }}>{status.text}</Text>
                                </View>
                                <Ionicons
                                  name={isExpanded ? "chevron-up" : "chevron-down"}
                                  size={20}
                                  color={theme.textSecondary}
                                  style={{ marginTop: 8 }}
                                />
                              </View>
                            </View>

                            {/* Item summary */}
                            <View style={{ flexDirection: 'row', gap: 12, flexWrap: 'wrap' }}>
                              <Text style={{ fontSize: 13, color: theme.textSecondary }}>
                                📦 {itemCount} eszköz
                              </Text>
                              <Text style={{ fontSize: 13, color: theme.secondary }}>
                                ✅ {foundCount} megtalálva
                              </Text>
                              <Text style={{ fontSize: 13, color: theme.danger }}>
                                ❌ {missingCount} hiányzik
                              </Text>
                            </View>
                          </TouchableOpacity>

                          {/* Expanded details */}
                          {isExpanded && (
                            <View style={{ marginTop: 16, paddingTop: 16, borderTopWidth: 1, borderTopColor: theme.border }}>
                              {/* Items list */}
                              <Text style={{ fontSize: 14, fontWeight: '600', color: theme.text, marginBottom: 12 }}>
                                📋 Rögzített eszközök:
                              </Text>
                              <View style={{ gap: 8 }}>
                                {items.map((recordedItem, index) => (
                                  <View
                                    key={index}
                                    style={{
                                      flexDirection: 'row',
                                      alignItems: 'center',
                                      padding: 10,
                                      borderRadius: 8,
                                      backgroundColor: recordedItem.is_present ? '#d1fae520' : '#fee2e220',
                                      borderWidth: 1,
                                      borderColor: recordedItem.is_present ? '#10B98140' : '#EF444440',
                                    }}
                                  >
                                    <Ionicons
                                      name={recordedItem.is_present ? "checkmark-circle" : "close-circle"}
                                      size={20}
                                      color={recordedItem.is_present ? '#10B981' : '#EF4444'}
                                      style={{ marginRight: 10 }}
                                    />
                                    <View style={{ flex: 1 }}>
                                      <Text style={{ color: theme.text, fontSize: 14 }}>
                                        Eszköz #{recordedItem.item_id}
                                      </Text>
                                      {recordedItem.note && (
                                        <Text style={{ color: theme.textSecondary, fontSize: 12, marginTop: 2 }}>
                                          💬 {recordedItem.note}
                                        </Text>
                                      )}
                                    </View>
                                    <Text style={{
                                      fontSize: 12,
                                      fontWeight: '600',
                                      color: recordedItem.is_present ? '#10B981' : '#EF4444',
                                    }}>
                                      {recordedItem.is_present ? 'Megtalálva' : 'Hiányzik'}
                                    </Text>
                                  </View>
                                ))}
                              </View>

                              {/* Comment input for pending submissions */}
                              {isPending && (
                                <View style={{ marginTop: 16 }}>
                                  <Text style={{ fontSize: 14, fontWeight: '600', color: theme.text, marginBottom: 8 }}>
                                    💬 Megjegyzés a munkásnak (opcionális):
                                  </Text>
                                  <TextInput
                                    style={{
                                      backgroundColor: theme.card,
                                      borderWidth: 1,
                                      borderColor: theme.border,
                                      borderRadius: 10,
                                      padding: 12,
                                      color: theme.text,
                                      fontSize: 14,
                                      minHeight: 80,
                                      textAlignVertical: 'top',
                                    }}
                                    placeholder="Írd ide a megjegyzésedet..."
                                    placeholderTextColor={theme.textSecondary}
                                    multiline
                                    value={reviewComment}
                                    onChangeText={setReviewComment}
                                  />

                                  {/* Action buttons */}
                                  <View style={{ flexDirection: 'row', marginTop: 16, gap: 12 }}>
                                    <TouchableOpacity
                                      style={[styles.btn, styles.btnSuccess, { flex: 1, paddingVertical: 14 }]}
                                      onPress={() => {
                                        showAlert(
                                          'Megerősítés',
                                          'Biztosan elfogadod ezt a beküldést?',
                                          [
                                            { text: 'Mégse', style: 'cancel' },
                                            { text: 'Elfogad', onPress: () => reviewSubmission(item.id, 'approved', reviewComment) }
                                          ]
                                        );
                                      }}
                                    >
                                      <Ionicons name="checkmark-circle" size={20} color="#fff" style={{ marginRight: 8 }} />
                                      <Text style={[styles.btnText, { fontSize: 15 }]}>Elfogad</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity
                                      style={[styles.btn, styles.btnDanger, { flex: 1, paddingVertical: 14 }]}
                                      onPress={() => {
                                        showAlert(
                                          'Megerősítés',
                                          'Biztosan elutasítod ezt a beküldést?',
                                          [
                                            { text: 'Mégse', style: 'cancel' },
                                            { text: 'Elutasít', style: 'destructive', onPress: () => reviewSubmission(item.id, 'rejected', reviewComment) }
                                          ]
                                        );
                                      }}
                                    >
                                      <Ionicons name="close-circle" size={20} color="#fff" style={{ marginRight: 8 }} />
                                      <Text style={[styles.btnText, { fontSize: 15 }]}>Elutasít</Text>
                                    </TouchableOpacity>
                                  </View>
                                </View>
                              )}
                            </View>
                          )}

                          {/* Collapsed action buttons (only if not expanded) */}
                          {!isExpanded && isPending && (
                            <View style={{ flexDirection: 'row', marginTop: 16, gap: 12 }}>
                              <TouchableOpacity
                                style={[styles.btn, styles.btnSuccess, { flex: 1, paddingVertical: 12 }]}
                                onPress={() => {
                                  setExpandedSubmissionId(item.id);
                                  setReviewComment('');
                                }}
                              >
                                <Ionicons name="eye" size={18} color="#fff" style={{ marginRight: 6 }} />
                                <Text style={styles.btnText}>Részletek</Text>
                              </TouchableOpacity>
                            </View>
                          )}
                        </View>
                      );
                    };

                    return (
                      <>
                        {/* Pending section header */}
                        {pendingItems.length > 0 && (
                          <View style={{ marginBottom: 12 }}>
                            <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 12 }}>
                              <Ionicons name="time" size={20} color="#F59E0B" style={{ marginRight: 8 }} />
                              <Text style={{ fontSize: 16, fontWeight: '600', color: theme.text }}>
                                Várakozik elbírálásra ({pendingItems.length})
                              </Text>
                            </View>
                            {pendingItems.map(renderEmployerSubmissionCard)}
                          </View>
                        )}

                        {/* Completed section with toggle */}
                        {completedItems.length > 0 && (
                          <View>
                            <TouchableOpacity
                              onPress={() => setShowCompletedSubmissions(!showCompletedSubmissions)}
                              style={{
                                flexDirection: 'row',
                                alignItems: 'center',
                                justifyContent: 'space-between',
                                paddingVertical: 12,
                                paddingHorizontal: 16,
                                backgroundColor: theme.card,
                                borderRadius: 10,
                                marginBottom: 12,
                                borderWidth: 1,
                                borderColor: theme.border,
                              }}
                            >
                              <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                                <Ionicons name="checkmark-done" size={20} color={theme.secondary} style={{ marginRight: 8 }} />
                                <Text style={{ fontSize: 16, fontWeight: '600', color: theme.text }}>
                                  Lezárt elbírálások ({completedItems.length})
                                </Text>
                              </View>
                              <Ionicons
                                name={showCompletedSubmissions ? "chevron-up" : "chevron-down"}
                                size={20}
                                color={theme.textSecondary}
                              />
                            </TouchableOpacity>

                            {showCompletedSubmissions && completedItems.map(renderEmployerSubmissionCard)}
                          </View>
                        )}
                      </>
                    );
                  })()}
                </>
              )}
            </ScrollView>
          </View>

          <View style={styles.bottomNav}>
            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('inventories')}
            >
              <Ionicons name="clipboard-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Leltárak</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.navBtn, styles.navBtnActive]}
              onPress={() => setCurrentScreen('submissions')}
            >
              <Ionicons name="document-text" size={24} color={theme.primary} />
              <Text style={[styles.navBtnText, styles.navBtnActiveText]}>Beküldések</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('workers')}
            >
              <Ionicons name="people-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Munkások</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('profile')}
            >
              <Ionicons name="person-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Profil</Text>
            </TouchableOpacity>
          </View>
        </AnimatedScreen>
      </SafeAreaView>
    );
  }

  // ==================== INVENTORY LIST SCREEN ====================

  function renderInventoryListScreen() {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AnimatedScreen>
          <View style={styles.header}>
            <View>
              <Text style={styles.headerTitle}>📋 Leltárak</Text>
              <View style={{ flexDirection: 'row', alignItems: 'center', marginTop: 4 }}>
                <View
                  style={{
                    backgroundColor: isOnline ? '#10B981' : '#EF4444',
                    paddingHorizontal: 10,
                    paddingVertical: 4,
                    borderRadius: 12,
                    flexDirection: 'row',
                    alignItems: 'center',
                  }}
                >
                  <Ionicons
                    name={isOnline ? 'cloud-done' : 'cloud-offline'}
                    size={12}
                    color="#fff"
                    style={{ marginRight: 4 }}
                  />
                  <Text style={{ color: '#fff', fontSize: 12, fontWeight: 'bold' }}>
                    {isOnline ? 'Online' : 'Offline'}
                  </Text>
                </View>
                {pendingSubmissions.length > 0 && (
                  <Text style={{ color: 'rgba(255,255,255,0.8)', fontSize: 12, marginLeft: 8 }}>
                    ({pendingSubmissions.length} várakozik)
                  </Text>
                )}
              </View>
              {user?.role === 'worker' && assignedCompany && (
                <Text style={styles.companyInfo}>
                  🏢 {assignedCompany.name}
                </Text>
              )}
            </View>
            <TouchableOpacity onPress={handleLogout}>
              <Text style={styles.headerBtn}>Kilépés</Text>
            </TouchableOpacity>
          </View>

          <View style={{ flex: 1 }}>
            <FlatList
              data={inventories.filter(inv => {
                // For workers: hide ALL inventories they've submitted (Beküldések is the log)
                if (user?.role === 'worker') {
                  const hasSubmission = mySubmissions.some(s => s.inventory_id === inv.id);
                  if (hasSubmission) {
                    return false; // Hide all submitted inventories
                  }
                }
                // For employers/admins: only show active inventories
                if (user?.role === 'employer' || user?.role === 'admin') {
                  return inv.status === 'active';
                }
                return true;
              })}
              keyExtractor={(item) => item.id.toString()}
              contentContainerStyle={{ padding: 16 }}
              refreshControl={
                <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
              }
              ListEmptyComponent={
                <View style={styles.empty}>
                  <Ionicons name="file-tray-outline" size={64} color={theme.textSecondary} style={{ marginBottom: 16 }} />
                  <Text style={styles.emptyText}>
                    {user?.role === 'worker' ? 'Nincs aktív leltár' : 'Nincs elérhető leltár'}
                  </Text>
                </View>
              }
              renderItem={({ item }) => {
                const isEmployer = user?.role === 'employer' || user?.role === 'admin';
                const CardWrapper = isEmployer ? View : TouchableOpacity;

                return (
                  <CardWrapper
                    style={styles.card}
                    onPress={!isEmployer ? () => selectInventory(item) : undefined}
                  >
                    <View style={[styles.row, { alignItems: 'flex-start' }]}>
                      <View style={{
                        marginRight: 16,
                        backgroundColor: theme.background,
                        padding: 10,
                        borderRadius: 12
                      }}>
                        <Ionicons name="clipboard-outline" size={28} color={theme.primary} />
                      </View>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.cardTitle}>{item.name}</Text>
                        <Text style={styles.cardMeta}>
                          <Ionicons name="calendar-outline" size={12} color={theme.textSecondary} /> {item.start_date} - {item.end_date}
                        </Text>
                        <View style={{
                          marginTop: 8,
                          paddingHorizontal: 8,
                          paddingVertical: 4,
                          borderRadius: 8,
                          alignSelf: 'flex-start',
                          backgroundColor: item.status === 'active' ? '#10B98120' : theme.surface,
                        }}>
                          <Text style={{
                            fontSize: 12,
                            fontWeight: '600',
                            color: item.status === 'active' ? '#10B981' : theme.textSecondary
                          }}>
                            {item.status === 'active' ? '● Aktív' : '○ ' + item.status}
                          </Text>
                        </View>
                        {isEmployer && (
                          <Text style={{ fontSize: 11, color: theme.textSecondary, marginTop: 4, fontStyle: 'italic' }}>
                            📋 Csak megtekintés
                          </Text>
                        )}
                      </View>
                      {!isEmployer && (
                        <View style={{ justifyContent: 'center' }}>
                          <Ionicons name="chevron-forward" size={20} color={theme.textSecondary} />
                        </View>
                      )}
                    </View>
                  </CardWrapper>
                );
              }}
            />
          </View>

          <View style={styles.bottomNav}>
            <TouchableOpacity
              style={[styles.navBtn, styles.navBtnActive]}
              onPress={() => setCurrentScreen('inventories')}
            >
              <Ionicons name="clipboard" size={24} color={theme.primary} />
              <Text style={[styles.navBtnText, styles.navBtnActiveText]}>Leltárak</Text>
            </TouchableOpacity>

            {user?.role === 'worker' && (
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => { setCurrentScreen('submissions'); fetchMySubmissions(); }}
              >
                <Ionicons name="send-outline" size={24} color={theme.textSecondary} />
                <Text style={styles.navBtnText}>Beküldések</Text>
              </TouchableOpacity>
            )}

            {(user?.role === 'employer' || user?.role === 'admin') && (
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => { setCurrentScreen('submissions'); fetchEmployerSubmissions(selectedCompanyId || companies[0]?.id); }}
              >
                <Ionicons name="document-text-outline" size={24} color={theme.textSecondary} />
                <Text style={styles.navBtnText}>Beküldések</Text>
              </TouchableOpacity>
            )}

            {(user?.role === 'employer' || user?.role === 'admin') && (
              <TouchableOpacity
                style={styles.navBtn}
                onPress={() => setCurrentScreen('workers')}
              >
                <Ionicons name="people-outline" size={24} color={theme.textSecondary} />
                <Text style={styles.navBtnText}>Munkások</Text>
              </TouchableOpacity>
            )}

            <TouchableOpacity
              style={styles.navBtn}
              onPress={() => setCurrentScreen('profile')}
            >
              <Ionicons name="person-outline" size={24} color={theme.textSecondary} />
              <Text style={styles.navBtnText}>Profil</Text>
            </TouchableOpacity>
          </View>
        </AnimatedScreen>
      </SafeAreaView >
    );
  }

  // ==================== INVENTORY SCREEN ====================

  function renderInventoryScreen() {
    return (
      <SafeAreaView style={styles.container}>
        <StatusBar style="light" />
        <AnimatedScreen>
          {/* Header */}
          <View style={styles.header}>
            <TouchableOpacity onPress={() => setSelectedInventory(null)} style={{ padding: 8 }}>
              <Ionicons name="arrow-back" size={24} color="#fff" />
            </TouchableOpacity>
            <Text style={styles.headerTitle} numberOfLines={1}>
              {selectedInventory.name}
            </Text>
            <View style={{ width: 60 }} />
          </View>

          <ScrollView style={styles.content}>
            {/* Manual Entry */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>📝 Eszköz rögzítése</Text>

              {/* QR Scanner Button */}
              <TouchableOpacity
                style={[styles.btn, styles.btnScanner]}
                onPress={openScanner}
              >
                <Text style={styles.btnText}>📷 QR Kód Beolvasás</Text>
              </TouchableOpacity>

              <Text style={styles.orDivider}>— vagy —</Text>

              <View style={styles.row}>
                <TextInput
                  style={[styles.input, { flex: 1, marginBottom: 0 }]}
                  placeholder="Eszköz ID"
                  placeholderTextColor="#999"
                  value={manualId}
                  onChangeText={setManualId}
                  keyboardType="number-pad"
                />
                <TouchableOpacity
                  style={[styles.btn, styles.btnSuccess, { marginLeft: 10 }]}
                  onPress={handleManualEntry}
                >
                  <Text style={styles.btnText}>OK</Text>
                </TouchableOpacity>
              </View>

            </View>

            <TouchableOpacity
              style={[styles.btn, styles.btnOutline, { marginTop: 12 }]}
              onPress={() => setShowItemsModal(true)}
            >
              <Text style={styles.btnOutlineText}>📋 Eszközlista megnyitása</Text>
            </TouchableOpacity>

            {/* Recorded Items */}
            <View style={[styles.section, { marginHorizontal: 16, marginBottom: 16 }]}>
              <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 12 }}>
                <View style={{ backgroundColor: theme.secondary + '20', padding: 8, borderRadius: 8, marginRight: 10 }}>
                  <Ionicons name="checkmark-done" size={20} color={theme.secondary} />
                </View>
                <Text style={styles.sectionTitle}>
                  Rögzítve ({recordedItems.length})
                </Text>
              </View>

              {recordedItems.length === 0 ? (
                <View style={{ alignItems: 'center', padding: 20 }}>
                  <Ionicons name="cube-outline" size={40} color={theme.textSecondary} style={{ marginBottom: 8 }} />
                  <Text style={styles.muted}>Még nem rögzítettél eszközt</Text>
                </View>
              ) : (
                recordedItems.map((item, index) => (
                  <View
                    key={item.id}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      backgroundColor: item.isPresent ? 'rgba(16, 185, 129, 0.08)' : 'rgba(239, 68, 68, 0.08)',
                      borderRadius: 12,
                      padding: 12,
                      marginBottom: index === recordedItems.length - 1 ? 0 : 10,
                      borderLeftWidth: 4,
                      borderLeftColor: item.isPresent ? theme.secondary : theme.danger,
                    }}
                  >
                    <View style={{
                      backgroundColor: item.isPresent ? theme.secondary : theme.danger,
                      padding: 8,
                      borderRadius: 10,
                      marginRight: 12
                    }}>
                      <Ionicons
                        name={item.isPresent ? 'checkmark' : 'close'}
                        size={18}
                        color="#fff"
                      />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.itemName, { marginBottom: 2 }]}>{item.name}</Text>
                      <Text style={{ fontSize: 12, color: theme.textSecondary }}>
                        {item.isPresent ? 'Megtalálva' : 'Hiányzik'} • {item.recordedAt}
                      </Text>
                    </View>
                    <TouchableOpacity
                      onPress={() => removeRecordedItem(item.id)}
                      style={{
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        padding: 8,
                        borderRadius: 8,
                      }}
                    >
                      <Ionicons name="trash-outline" size={18} color={theme.danger} />
                    </TouchableOpacity>
                  </View>
                ))
              )}
            </View>

          </ScrollView>

          {/* Fixed Submit Button */}
          {recordedItems.length > 0 && (
            <View style={{
              backgroundColor: theme.surface,
              paddingHorizontal: 16,
              paddingTop: 12,
              paddingBottom: 24,
              borderTopWidth: 1,
              borderTopColor: theme.border,
              shadowColor: '#000',
              shadowOffset: { width: 0, height: -4 },
              shadowOpacity: 0.1,
              shadowRadius: 8,
              elevation: 10,
            }}>
              <TouchableOpacity
                style={[styles.btn, styles.btnPrimary]}
                onPress={submitInventory}
                disabled={loading}
              >
                {loading ? (
                  <ActivityIndicator color="#fff" />
                ) : (
                  <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                    <Ionicons name="cloud-upload" size={20} color="#fff" style={{ marginRight: 8 }} />
                    <Text style={styles.btnText}>Beküldés ({recordedItems.length} eszköz)</Text>
                  </View>
                )}
              </TouchableOpacity>
            </View>
          )}

          {/* QR Scanner Modal */}
          <Modal visible={showScanner} animationType="slide">
            <SafeAreaView style={styles.scannerContainer}>
              <View style={styles.scannerHeader}>
                <Text style={styles.scannerTitle}>QR Kód Beolvasás</Text>
                <TouchableOpacity onPress={() => setShowScanner(false)}>
                  <Text style={styles.modalClose}>Bezárás</Text>
                </TouchableOpacity>
              </View>

              <CameraView
                style={styles.camera}
                facing="back"
                barcodeScannerSettings={{
                  barcodeTypes: ['qr', 'ean13', 'ean8', 'code128', 'code39'],
                }}
                onBarcodeScanned={scanned ? undefined : handleBarcodeScanned}
              />

              <View style={styles.scannerOverlay}>
                <View style={styles.scannerFrame} />
              </View>

              <View style={styles.scannerFooter}>
                <Text style={styles.scannerHint}>
                  Tartsd a kamerát a QR kód fölé
                </Text>
                {scanned && (
                  <TouchableOpacity
                    style={[styles.btn, styles.btnPrimary, { marginTop: 12 }]}
                    onPress={() => setScanned(false)}
                  >
                    <Text style={styles.btnText}>Újra beolvasás</Text>
                  </TouchableOpacity>
                )}
              </View>
            </SafeAreaView>
          </Modal>

          {/* Items Modal */}
          <Modal visible={showItemsModal} animationType="slide">
            <SafeAreaView style={styles.modal}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Eszközök</Text>
                <TouchableOpacity onPress={() => setShowItemsModal(false)}>
                  <Text style={styles.modalClose}>Bezárás</Text>
                </TouchableOpacity>
              </View>

              <FlatList
                data={items}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={{ padding: 16 }}
                ListEmptyComponent={
                  <Text style={styles.muted}>Nincs eszköz a leltárban</Text>
                }
                renderItem={({ item }) => {
                  const recorded = recordedItems.find((r) => r.id === item.id);
                  return (
                    <TouchableOpacity
                      style={[styles.modalItem, recorded && styles.modalItemDone]}
                      onPress={() => {
                        if (!recorded) {
                          showAlert(item.name, 'Mi az eszköz állapota?', [
                            { text: '✅ Megvan', onPress: () => recordItem(item, true) },
                            { text: '❌ Hiányzik', onPress: () => recordItem(item, false) },
                            { text: 'Mégse', style: 'cancel' },
                          ]);
                        }
                      }}
                      disabled={!!recorded}
                    >

                      <View>
                        <Text style={styles.modalItemName}>{item.name}</Text>
                        <Text style={styles.modalItemId}>ID: {item.id}</Text>
                      </View>
                      {recorded && (
                        <Text style={{ fontSize: 20 }}>
                          {recorded.isPresent ? '✅' : '❌'}
                        </Text>
                      )}
                    </TouchableOpacity>
                  );
                }}
              />
            </SafeAreaView>
          </Modal>
        </AnimatedScreen>
      </SafeAreaView>
    );
  }

  return (
    <>
      <CustomAlert
        visible={alertConfig.visible}
        title={alertConfig.title}
        message={alertConfig.message}
        buttons={alertConfig.buttons}
        theme={theme}
        onClose={closeAlert}
      />
      {/* Render screens based on state */}
      {isLoggedIn ? (
        selectedInventory ? (
          renderInventoryScreen()
        ) : (
          <>
            {currentScreen === 'inventories' && renderInventoryListScreen()}
            {currentScreen === 'profile' && renderProfileScreen()}
            {currentScreen === 'workers' && (user?.role === 'employer' || user?.role === 'admin') && renderWorkersScreen()}
            {currentScreen === 'submissions' && user?.role === 'worker' && renderSubmissionsScreen()}
            {currentScreen === 'submissions' && (user?.role === 'employer' || user?.role === 'admin') && renderEmployerReviewScreen()}
          </>
        )
      ) : (
        renderLoginScreen()
      )}
    </>
  );
}

// ==================== STYLES ====================

const getThemeStyles = (theme) => StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme.background,
  },
  // Login
  loginWrapper: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
    backgroundColor: theme.primary,
  },
  logo: {
    fontSize: 64,
    marginBottom: 8,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#fff',
  },
  subtitle: {
    fontSize: 16,
    color: 'rgba(255,255,255,0.8)',
    marginBottom: 32,
  },
  form: {
    width: '100%',
    backgroundColor: theme.surface,
    borderRadius: 16,
    padding: 20,
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: theme.background,
    borderRadius: 12,
    paddingHorizontal: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: theme.border,
    height: 56,
  },
  inputIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    fontSize: 16,
    color: theme.text,
    height: '100%',
  },
  btn: {
    padding: 16,
    borderRadius: 10,
    alignItems: 'center',
  },
  btnPrimary: {
    backgroundColor: theme.primary,
  },
  btnSuccess: {
    backgroundColor: theme.secondary,
    paddingHorizontal: 20,
  },
  btnOutline: {
    backgroundColor: theme.surface,
    borderWidth: 1,
    borderColor: theme.border,
  },
  btnDisabled: {
    opacity: 0.6,
  },
  btnText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  btnOutlineText: {
    color: theme.text,
    fontSize: 16,
    fontWeight: '500',
  },
  apiUrlText: {
    fontSize: 11,
    color: theme.textSecondary,
    textAlign: 'center',
    marginTop: 12,
    fontFamily: 'monospace',
  },
  // Header
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: theme.primary,
    paddingHorizontal: 16,
    paddingTop: 45, // Tighter fit
    paddingBottom: 12, // Tighter fit
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
    elevation: 6,
    zIndex: 10,
  },
  headerTitle: {
    color: '#fff',
    fontSize: 20,
    fontWeight: '800',
    flex: 1,
    textAlign: 'center',
    letterSpacing: 0.5,
  },
  headerBtn: {
    color: '#fff',
    fontSize: 16,
  },
  // List
  list: {
    padding: 16,
  },
  card: {
    backgroundColor: theme.surface,
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
    borderWidth: 1,
    borderColor: theme.border,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: theme.text,
    marginBottom: 4,
  },
  cardMeta: {
    fontSize: 14,
    color: theme.textSecondary,
    marginBottom: 8,
  },
  badge: {
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  badgeGreen: {
    backgroundColor: 'rgba(16, 185, 129, 0.1)', // theme.secondary low opacity
  },
  badgeYellow: {
    backgroundColor: 'rgba(245, 158, 11, 0.1)',
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '600',
    color: theme.text,
  },
  // Content
  content: {
    flex: 1,
  },
  section: {
    backgroundColor: theme.surface,
    margin: 16,
    marginBottom: 8,
    padding: 16,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: theme.border,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: theme.text,
    marginBottom: 12,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  muted: {
    color: theme.textSecondary,
    textAlign: 'center',
    paddingVertical: 16,
  },
  // Recorded Items
  recordedItem: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 10,
    marginBottom: 8,
    borderLeftWidth: 4,
    backgroundColor: theme.background,
  },
  itemFound: {
    backgroundColor: 'rgba(16, 185, 129, 0.05)',
    borderLeftColor: theme.secondary,
  },
  itemMissing: {
    backgroundColor: 'rgba(239, 68, 68, 0.05)',
    borderLeftColor: theme.danger,
  },
  itemName: {
    fontSize: 16,
    fontWeight: '600',
    color: theme.text,
  },
  itemMeta: {
    fontSize: 13,
    color: theme.textSecondary,
    marginTop: 2,
  },
  removeBtn: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: theme.danger,
    alignItems: 'center',
    justifyContent: 'center',
  },
  removeBtnText: {
    color: '#fff',
    fontWeight: 'bold',
  },
  // Empty
  empty: {
    alignItems: 'center',
    paddingVertical: 60,
  },
  emptyIcon: {
    fontSize: 48,
    marginBottom: 12,
  },
  emptyText: {
    fontSize: 16,
    color: theme.textSecondary,
  },
  // Modal
  modal: {
    flex: 1,
    backgroundColor: theme.background,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: theme.surface,
    borderBottomWidth: 1,
    borderBottomColor: theme.border,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: theme.text,
  },
  modalClose: {
    fontSize: 16,
    color: theme.primary,
    fontWeight: '600',
  },
  modalItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: theme.surface,
    padding: 16,
    borderRadius: 10,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: theme.border,
  },
  modalItemDone: {
    opacity: 0.5,
  },
  modalItemName: {
    fontSize: 16,
    fontWeight: '600',
    color: theme.text,
  },
  modalItemId: {
    fontSize: 13,
    color: theme.textSecondary,
    marginTop: 2,
  },
  // Scanner
  btnScanner: {
    backgroundColor: '#9B59B6', // Keep or change?
  },
  orDivider: {
    textAlign: 'center',
    color: theme.textSecondary,
    marginVertical: 12,
    fontSize: 14,
  },
  scannerContainer: {
    flex: 1,
    backgroundColor: '#000',
  },
  scannerHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: 'rgba(0,0,0,0.8)',
  },
  scannerTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#fff',
  },
  camera: {
    flex: 1,
  },
  scannerOverlay: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scannerFrame: {
    width: 250,
    height: 250,
    borderWidth: 2,
    borderColor: theme.primary,
    borderRadius: 20,
    backgroundColor: 'transparent',
  },
  scannerFooter: {
    padding: 20,
    backgroundColor: 'rgba(0,0,0,0.8)',
    alignItems: 'center',
  },
  scannerHint: {
    color: '#fff',
    fontSize: 16,
    textAlign: 'center',
  },
  // Offline mode
  onlineStatus: {
    fontSize: 12,
    color: theme.secondary,
    marginTop: 4,
  },
  offlineStatus: {
    color: theme.danger,
  },
  companyInfo: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
    fontWeight: '500',
  },
  // Profile Screen
  infoRow: {
    marginBottom: 12,
    padding: 12,
    backgroundColor: theme.background,
    borderRadius: 8,
  },
  infoLabel: {
    fontSize: 13,
    color: theme.textSecondary,
    marginBottom: 4,
  },
  infoValue: {
    fontSize: 16,
    color: theme.text,
    fontWeight: '500',
  },
  divider: {
    height: 1,
    backgroundColor: theme.border,
    marginVertical: 16,
  },
  // Workers Screen
  workerItem: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: theme.surface,
    padding: 14,
    borderRadius: 10,
    marginBottom: 10,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 5,
    elevation: 1,
    borderWidth: 1,
    borderColor: theme.border,
  },
  workerName: {
    fontSize: 16,
    fontWeight: '600',
    color: theme.text,
  },
  workerMeta: {
    fontSize: 13,
    color: theme.textSecondary,
    marginTop: 2,
  },
  companyTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: theme.primary,
    marginBottom: 10,
    marginTop: 6,
  },
  btnDanger: {
    backgroundColor: theme.danger,
    paddingVertical: 8,
    paddingHorizontal: 12,
  },
  // Bottom Navigation
  bottomNav: {
    flexDirection: 'row',
    backgroundColor: theme.surface,
    borderTopWidth: 1,
    borderTopColor: theme.border,
    paddingVertical: 8,
  },
  navBtn: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 12,
  },
  navBtnText: {
    fontSize: 13,
    color: theme.textSecondary,
  },
  navBtnActive: {
    borderTopWidth: 2,
    borderTopColor: theme.primary,
  },
  navBtnActiveText: {
    color: theme.primary,
    fontWeight: '600',
  },
});

