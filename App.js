import React, { useMemo, useState } from 'react';
import {
  Alert,
  Pressable,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { StatusBar } from 'expo-status-bar';

const listings = [
  { id: 'skyline', title: 'Skyline Loft', city: 'New York', price: 220, details: '2 guests · 1 bed · Fast Wi-Fi', hostId: 'host@doorkey.demo' },
  { id: 'harbor', title: 'Harbor View', city: 'Seattle', price: 185, details: '4 guests · 2 beds · EV charging', hostId: 'host@doorkey.demo' },
  { id: 'sunset', title: 'Sunset A-Frame', city: 'Austin', price: 260, details: '3 guests · Patio · Smart lock', hostId: 'host@doorkey.demo' },
];

const initialAccounts = [
  { id: 'demo-host', name: 'DoorKey Host', email: 'host@doorkey.demo', password: 'host123', role: 'Host', active: true },
  { id: 'demo-admin', name: 'DoorKey Admin', email: 'admin@doorkey.demo', password: 'admin123', role: 'Admin', active: true },
];

const makeId = () => `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
const makeAccessCode = () => String(Math.floor(100000 + Math.random() * 900000));

function ActionButton({ label, onPress, variant = 'primary', disabled = false }) {
  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled}
      onPress={onPress}
      style={[styles.button, styles[`${variant}Button`], disabled && styles.disabledButton]}
    >
      <Text style={[styles.buttonText, variant === 'secondary' && styles.secondaryButtonText]}>
        {label}
      </Text>
    </Pressable>
  );
}

function Field({ label, value, onChangeText, placeholder, secureTextEntry = false, keyboardType = 'default', maxLength }) {
  return (
    <View style={styles.fieldGroup}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        accessibilityLabel={label}
        autoCapitalize={label === 'Full name' ? 'words' : 'none'}
        keyboardType={keyboardType}
        maxLength={maxLength}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#71819b"
        secureTextEntry={secureTextEntry}
        style={styles.input}
        value={value}
      />
    </View>
  );
}

function StatusBadge({ status }) {
  const tone = status === 'Approved' ? 'approved' : status === 'Denied' ? 'denied' : 'pending';
  return (
    <Text style={[styles.statusBadge, styles[`${tone}Badge`]]}>
      {status}
    </Text>
  );
}

export default function App() {
  const [accounts, setAccounts] = useState(initialAccounts);
  const [bookings, setBookings] = useState([]);
  const [user, setUser] = useState(null);
  const [screen, setScreen] = useState('login');
  const [isRegistering, setIsRegistering] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [codeInputs, setCodeInputs] = useState({});

  const activeBookings = useMemo(
    () => bookings.filter((booking) => booking.status !== 'Denied').length,
    [bookings],
  );

  const signIn = () => {
    const account = accounts.find(
      (item) => item.email.toLowerCase() === email.trim().toLowerCase() && item.password === password,
    );
    if (!account) {
      Alert.alert('Sign in failed', 'Check your email and password, then try again.');
      return;
    }
    if (!account.active) {
      Alert.alert('Account disabled', 'Please contact a DoorKey administrator.');
      return;
    }
    setUser(account);
    setScreen('app');
    setPassword('');
  };

  const registerGuest = () => {
    const cleanName = name.trim();
    const cleanEmail = email.trim().toLowerCase();
    if (!cleanName || !cleanEmail || !password) {
      Alert.alert('Missing information', 'Enter your name, email, and password to register.');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cleanEmail)) {
      Alert.alert('Invalid email', 'Enter a valid email address.');
      return;
    }
    if (password.length < 6) {
      Alert.alert('Password too short', 'Use at least 6 characters for this demo account.');
      return;
    }
    if (accounts.some((item) => item.email.toLowerCase() === cleanEmail)) {
      Alert.alert('Account exists', 'An account already uses this email. Please sign in.');
      return;
    }

    const newAccount = {
      id: makeId(),
      name: cleanName,
      email: cleanEmail,
      password,
      role: 'Guest',
      active: true,
    };
    setAccounts((current) => [...current, newAccount]);
    setUser(newAccount);
    setScreen('app');
    setName('');
    setPassword('');
  };

  const signOut = () => {
    setUser(null);
    setScreen('login');
    setIsRegistering(false);
    setEmail('');
    setPassword('');
  };

  const bookListing = (listing) => {
    if (bookings.some((booking) => booking.listingId === listing.id && booking.status !== 'Denied')) {
      Alert.alert('Unavailable', 'This listing already has a booking in this demo. Try another home.');
      return;
    }
    const booking = {
      id: makeId(),
      guestId: user.id,
      guestName: user.name,
      listingId: listing.id,
      listingTitle: listing.title,
      hostId: listing.hostId,
      price: listing.price,
      code: makeAccessCode(),
      status: 'Pending',
      goEnabled: false,
    };
    setBookings((current) => [...current, booking]);
    Alert.alert('Booking requested', `${listing.title} is booked. Your access code is ${booking.code}.`);
  };

  const updateBooking = (bookingId, changes) => {
    setBookings((current) => current.map((booking) => (
      booking.id === bookingId ? { ...booking, ...changes } : booking
    )));
  };

  const hostApprove = (booking) => {
    const enteredCode = (codeInputs[booking.id] || '').trim();
    if (enteredCode !== booking.code) {
      Alert.alert('Code does not match', 'Enter the guest’s six-digit booking code to approve and enable Go.');
      return;
    }
    updateBooking(booking.id, { status: 'Approved', goEnabled: true });
    setCodeInputs((current) => ({ ...current, [booking.id]: '' }));
    Alert.alert('Booking approved', 'The guest can now see and use the Go button.');
  };

  const adminSetBooking = (bookingId, status) => {
    updateBooking(bookingId, { status, goEnabled: status === 'Approved' });
  };

  const adminDeleteBooking = (bookingId) => {
    Alert.alert('Remove booking?', 'This permanently removes the booking from this demo session.', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Remove',
        style: 'destructive',
        onPress: () => setBookings((current) => current.filter((booking) => booking.id !== bookingId)),
      },
    ]);
  };

  const toggleAccount = (accountId) => {
    if (accountId === user.id) {
      Alert.alert('Action unavailable', 'You cannot disable the account you are currently using.');
      return;
    }
    setAccounts((current) => current.map((account) => (
      account.id === accountId ? { ...account, active: !account.active } : account
    )));
  };

  const renderLogin = () => (
    <View style={styles.panel}>
      <View style={styles.introCard}>
        <Text style={styles.eyebrow}>Smart stay platform</Text>
        <Text style={styles.title}>Welcome to DoorKey</Text>
        <Text style={styles.bodyText}>Sign in to continue. Guests can create an account; host and admin access is provided for this demo.</Text>
      </View>
      {isRegistering && (
        <Field label="Full name" value={name} onChangeText={setName} placeholder="Your name" />
      )}
      <Field label="Email" value={email} onChangeText={setEmail} placeholder="you@example.com" keyboardType="email-address" />
      <Field label="Password" value={password} onChangeText={setPassword} placeholder="At least 6 characters" secureTextEntry />
      <ActionButton label={isRegistering ? 'Create guest account' : 'Sign in'} onPress={isRegistering ? registerGuest : signIn} />
      <ActionButton
        label={isRegistering ? 'I already have an account' : 'Register as a guest'}
        onPress={() => setIsRegistering((current) => !current)}
        variant="secondary"
      />
      <View style={styles.demoCard}>
        <Text style={styles.sectionTitle}>Demo accounts</Text>
        <Text style={styles.bodyText}>Host: host@doorkey.demo · password: host123</Text>
        <Text style={styles.bodyText}>Admin: admin@doorkey.demo · password: admin123</Text>
      </View>
      <Text style={styles.noticeText}>Demo only: accounts and bookings are held in memory and reset when the app restarts. Use a secure backend before real use.</Text>
    </View>
  );

  const renderGuest = () => {
    const myBookings = bookings.filter((booking) => booking.guestId === user.id);
    return (
      <View style={styles.panel}>
        <Text style={styles.sectionTitle}>Browse stays</Text>
        {listings.map((listing) => (
          <View key={listing.id} style={styles.card}>
            <View style={styles.row}>
              <Text style={styles.cardTitle}>{listing.title}</Text>
              <Text style={styles.price}>${listing.price}/night</Text>
            </View>
            <Text style={styles.subtleText}>{listing.city} · {listing.details}</Text>
            <ActionButton
              label={bookings.some((booking) => booking.listingId === listing.id && booking.status !== 'Denied') ? 'Unavailable' : 'Book this stay'}
              onPress={() => bookListing(listing)}
              disabled={bookings.some((booking) => booking.listingId === listing.id && booking.status !== 'Denied')}
            />
          </View>
        ))}
        <Text style={styles.sectionTitle}>My bookings</Text>
        {myBookings.length === 0 ? (
          <Text style={styles.emptyText}>Your bookings and access codes will appear here.</Text>
        ) : myBookings.map((booking) => (
          <View key={booking.id} style={styles.card}>
            <View style={styles.row}>
              <Text style={styles.cardTitle}>{booking.listingTitle}</Text>
              <StatusBadge status={booking.status} />
            </View>
            {booking.status === 'Denied' ? (
              <Text style={styles.subtleText}>This booking was denied. Contact support if you need help.</Text>
            ) : (
              <>
                <Text style={styles.subtleText}>Your booking access code</Text>
                <Text selectable style={styles.accessCode}>{booking.code}</Text>
                {booking.goEnabled ? (
                  <ActionButton
                    label="Go — check in"
                    onPress={() => Alert.alert('Check-in ready', `Your booking for ${booking.listingTitle} is approved.`)}
                    variant="success"
                  />
                ) : (
                  <Text style={styles.noticeText}>Waiting for your host to match the code and approve your booking.</Text>
                )}
              </>
            )}
          </View>
        ))}
      </View>
    );
  };

  const renderHost = () => {
    const hostBookings = bookings.filter((booking) => booking.hostId === user.email);
    return (
      <View style={styles.panel}>
        <Text style={styles.sectionTitle}>Booking requests</Text>
        <Text style={styles.subtleText}>Match the guest’s code with the code shown on their booking before approving.</Text>
        {hostBookings.length === 0 ? (
          <Text style={styles.emptyText}>New guest booking requests will appear here.</Text>
        ) : hostBookings.map((booking) => (
          <View key={booking.id} style={styles.card}>
            <View style={styles.row}>
              <Text style={styles.cardTitle}>{booking.listingTitle}</Text>
              <StatusBadge status={booking.status} />
            </View>
            <Text style={styles.subtleText}>Guest: {booking.guestName}</Text>
            <Text style={styles.subtleText}>Ask the guest to provide their six-digit code.</Text>
            {booking.status === 'Pending' && (
              <>
                <Field
                  label="Confirm guest code"
                  value={codeInputs[booking.id] || ''}
                  onChangeText={(value) => setCodeInputs((current) => ({ ...current, [booking.id]: value.replace(/\D/g, '').slice(0, 6) }))}
                  placeholder="Enter 6-digit code"
                  keyboardType="number-pad"
                  maxLength={6}
                />
                <ActionButton label="Match code & approve" onPress={() => hostApprove(booking)} />
                <ActionButton label="Deny booking" onPress={() => updateBooking(booking.id, { status: 'Denied', goEnabled: false })} variant="danger" />
              </>
            )}
            {booking.status === 'Approved' && <Text style={styles.successText}>Approved — guest Go button enabled.</Text>}
          </View>
        ))}
      </View>
    );
  };

  const renderAdmin = () => (
    <View style={styles.panel}>
      <View style={styles.metricsGrid}>
        <View style={styles.metricCard}><Text style={styles.metricLabel}>Bookings</Text><Text style={styles.metricValue}>{bookings.length}</Text></View>
        <View style={styles.metricCard}><Text style={styles.metricLabel}>Active bookings</Text><Text style={styles.metricValue}>{activeBookings}</Text></View>
        <View style={styles.metricCard}><Text style={styles.metricLabel}>Accounts</Text><Text style={styles.metricValue}>{accounts.length}</Text></View>
        <View style={styles.metricCard}><Text style={styles.metricLabel}>Pending</Text><Text style={styles.metricValue}>{bookings.filter((booking) => booking.status === 'Pending').length}</Text></View>
      </View>
      <Text style={styles.sectionTitle}>All bookings</Text>
      {bookings.length === 0 ? (
        <Text style={styles.emptyText}>Bookings from all guests will appear here.</Text>
      ) : bookings.map((booking) => (
        <View key={booking.id} style={styles.card}>
          <View style={styles.row}>
            <Text style={styles.cardTitle}>{booking.listingTitle}</Text>
            <StatusBadge status={booking.status} />
          </View>
          <Text style={styles.subtleText}>{booking.guestName} · Code {booking.code}</Text>
          <View style={styles.buttonRow}>
            <ActionButton label="Approve & enable Go" onPress={() => adminSetBooking(booking.id, 'Approved')} variant="success" />
            <ActionButton label="Deny" onPress={() => adminSetBooking(booking.id, 'Denied')} variant="danger" />
            <ActionButton label="Remove booking" onPress={() => adminDeleteBooking(booking.id)} variant="secondary" />
          </View>
        </View>
      ))}
      <Text style={styles.sectionTitle}>Account controls</Text>
      {accounts.map((account) => (
        <View key={account.id} style={styles.card}>
          <View style={styles.row}>
            <View>
              <Text style={styles.cardTitle}>{account.name}</Text>
              <Text style={styles.subtleText}>{account.email} · {account.role}</Text>
            </View>
            <Text style={account.active ? styles.successText : styles.deniedText}>{account.active ? 'Active' : 'Disabled'}</Text>
          </View>
          {account.id !== user.id && (
            <ActionButton
              label={account.active ? 'Disable account' : 'Enable account'}
              onPress={() => toggleAccount(account.id)}
              variant="secondary"
            />
          )}
        </View>
      ))}
      <Text style={styles.noticeText}>Administrator actions here affect this demo session only.</Text>
    </View>
  );

  if (screen === 'login') {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar style="light" />
        <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
          {renderLogin()}
        </ScrollView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="light" />
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        <View style={styles.header}>
          <View>
            <Text style={styles.eyebrow}>{user.role} account</Text>
            <Text style={styles.title}>DoorKey</Text>
            <Text style={styles.subtleText}>Signed in as {user.name}</Text>
          </View>
          <Pressable accessibilityRole="button" onPress={signOut} style={styles.signOut}>
            <Text style={styles.signOutText}>Sign out</Text>
          </Pressable>
        </View>
        <Text style={styles.greeting}>
          {user.role === 'Guest' ? 'Find a stay and manage your access' : user.role === 'Host' ? 'Review and approve guest bookings' : 'Platform administration'}
        </Text>
        {user.role === 'Guest' && renderGuest()}
        {user.role === 'Host' && renderHost()}
        {user.role === 'Admin' && renderAdmin()}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#0b1220' },
  container: { flexGrow: 1, paddingHorizontal: 18, paddingVertical: 24 },
  panel: { gap: 14 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  eyebrow: { color: '#8fb4ff', fontSize: 12, letterSpacing: 1.5, textTransform: 'uppercase', marginBottom: 4 },
  title: { color: '#f8fbff', fontSize: 28, fontWeight: '800' },
  greeting: { color: '#dfeafc', fontSize: 18, fontWeight: '700', marginBottom: 18 },
  introCard: { backgroundColor: '#111d2f', borderRadius: 18, padding: 18, borderWidth: 1, borderColor: '#1c2e4f', gap: 8, marginBottom: 4 },
  bodyText: { color: '#c4d2e7', fontSize: 14, lineHeight: 20 },
  fieldGroup: { gap: 7 },
  fieldLabel: { color: '#c4d2e7', fontSize: 13, fontWeight: '700' },
  input: { color: '#f8fbff', backgroundColor: '#121f36', borderWidth: 1, borderColor: '#2a3d5d', borderRadius: 12, paddingHorizontal: 14, paddingVertical: 12, fontSize: 16 },
  button: { minHeight: 46, justifyContent: 'center', alignItems: 'center', borderRadius: 12, paddingHorizontal: 14, paddingVertical: 12, marginTop: 4 },
  primaryButton: { backgroundColor: '#2d7df6' },
  secondaryButton: { backgroundColor: '#1b2d48', borderWidth: 1, borderColor: '#294367' },
  successButton: { backgroundColor: '#4ecb9a' },
  dangerButton: { backgroundColor: '#652d3b' },
  disabledButton: { opacity: 0.45 },
  buttonText: { color: '#fff', fontWeight: '800', textAlign: 'center' },
  secondaryButtonText: { color: '#edf5ff' },
  signOut: { backgroundColor: '#1b2d48', paddingHorizontal: 14, paddingVertical: 10, borderRadius: 12 },
  signOutText: { color: '#edf5ff', fontWeight: '700' },
  demoCard: { backgroundColor: '#101b2e', borderRadius: 16, padding: 14, gap: 6, marginTop: 8 },
  noticeText: { color: '#9eb0ca', fontSize: 12, lineHeight: 18, marginTop: 4 },
  sectionTitle: { color: '#edf4ff', fontSize: 18, fontWeight: '700', marginTop: 6 },
  card: { backgroundColor: '#111d2f', borderRadius: 18, padding: 16, borderWidth: 1, borderColor: '#1c2e4f', gap: 10 },
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  cardTitle: { color: '#f2f7ff', fontSize: 17, fontWeight: '700', flexShrink: 1 },
  price: { color: '#9be7c2', fontSize: 15, fontWeight: '800' },
  subtleText: { color: '#aebfda', fontSize: 13, lineHeight: 19 },
  statusBadge: { overflow: 'hidden', borderRadius: 999, paddingHorizontal: 10, paddingVertical: 5, fontSize: 12, fontWeight: '800' },
  pendingBadge: { color: '#ffd166', backgroundColor: '#332611' },
  approvedBadge: { color: '#98edbf', backgroundColor: '#143326' },
  deniedBadge: { color: '#ffb2b2', backgroundColor: '#3a2028' },
  accessCode: { color: '#9be7c2', fontSize: 30, fontWeight: '900', letterSpacing: 8, paddingVertical: 2 },
  successText: { color: '#98edbf', fontWeight: '700', fontSize: 14 },
  deniedText: { color: '#ffb2b2', fontWeight: '700', fontSize: 14 },
  emptyText: { color: '#9eb0ca', backgroundColor: '#111d2f', borderRadius: 14, padding: 16, lineHeight: 21 },
  metricsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, justifyContent: 'space-between' },
  metricCard: { width: '48%', borderRadius: 16, padding: 14, minHeight: 82, justifyContent: 'center', backgroundColor: '#12274e' },
  metricLabel: { color: '#cfe2ff', fontSize: 12, marginBottom: 8 },
  metricValue: { color: '#fff', fontSize: 24, fontWeight: '800' },
  buttonRow: { gap: 8 },
});
