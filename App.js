import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, RefreshControl, Alert, Platform, StatusBar, ActivityIndicator, Modal, StyleSheet } from 'react-native';
import { API } from './config'; // your PC's IP lives in config.js, so updates to this file never erase it


const C = { brand: '#e11d5a', line: '#dcdcdc', mu: '#6b6b6b', tx: '#222', side: '#f7f7f7' };
let TOKEN = null;

async function call(path, method = 'GET', body) {
  let res;
  const ctl = new AbortController();
  const timer = setTimeout(() => ctl.abort(), 10000); // give up after 10 seconds instead of waiting forever
  try {
    res = await fetch(API + path, {
      method,
      signal: ctl.signal,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(TOKEN ? { Authorization: 'Bearer ' + TOKEN } : {}) },
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch (e) {
    throw new Error('Cannot reach the server. Check the IP address in config.js, that php artisan serve is running with --host=0.0.0.0, and that your phone is on the same Wi-Fi.');
  } finally {
    clearTimeout(timer);
  }
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error((data.errors && Object.values(data.errors)[0][0]) || data.message || 'Something went wrong.');
  return data;
}

// Loads one or more endpoints. data is an array in the same order as the paths.
function useLoad(paths) {
  const key = (Array.isArray(paths) ? paths : [paths]).join('|');
  const [data, setData] = useState(null);
  const [err, setErr] = useState('');
  const [ref, setRef] = useState(false);
  const load = useCallback(async () => {
    try { setErr(''); setData(await Promise.all(key.split('|').map((p) => call(p)))); } catch (e) { setErr(e.message); }
    setRef(false);
  }, [key]);
  useEffect(() => { load(); }, [load]);
  return { data, err, ref, silent: load, reload: () => { setRef(true); load(); } };
}

async function run(fn, hook) {
  try { await fn(); hook.silent(); } catch (e) { Alert.alert('Could not complete', e.message); }
}
const ask = (title, onYes) => Alert.alert(title, '', [{ text: 'Keep it', style: 'cancel' }, { text: 'Confirm', onPress: onYes }]);
const cap = (x) => (x ? x[0].toUpperCase() + x.slice(1) : '');
const kind = (st) => (['Approved', 'active', 'success', 'Active'].includes(st) ? 'ok' : ['Denied', 'Cancelled', 'failed', 'Disabled'].includes(st) ? 'bad' : 'w');

const Badge = ({ t, k }) => {
  const m = { ok: ['#e3f4e8', '#117a3a'], bad: ['#fde8e4', '#c2321b'], w: ['#fff1d6', '#8a5a00'] }[k] || ['#ececec', '#555'];
  return <View style={[s.badge, { backgroundColor: m[0] }]}><Text style={{ color: m[1], fontSize: 12, fontWeight: '600' }}>{t}</Text></View>;
};
const Btn = ({ t, onPress, p }) => (
  <TouchableOpacity onPress={onPress} style={[s.btn, p && s.btnP]}><Text style={[s.btnT, p && { color: '#fff' }]}>{t}</Text></TouchableOpacity>
);
const Card = ({ children }) => <View style={s.card}>{children}</View>;
const Empty = ({ t }) => <Text style={s.empty}>{t}</Text>;

function Page({ title, sub, hook, children }) {
  return (
    <ScrollView contentContainerStyle={s.page} refreshControl={<RefreshControl refreshing={hook.ref} onRefresh={hook.reload} />}>
      <Text style={s.h1}>{title}</Text>
      {sub ? <Text style={s.sub}>{sub}</Text> : null}
      {hook.err ? <Text style={s.err}>{hook.err}</Text> : null}
      {!hook.data && !hook.err ? <ActivityIndicator color={C.brand} /> : children}
    </ScrollView>
  );
}
const Stats = ({ items }) => (
  <View style={s.stats}>{items.map(([l, v]) => <View key={l} style={s.stat}><Text style={s.mu}>{l}</Text><Text style={s.big}>{v}</Text></View>)}</View>
);

// Host: Approve / Decline.  Admin: Approve / Cancel.
function BookingCard({ b, hook, role }) {
  const host = role === 'Host';
  const go = (approve) => run(() => host
    ? call(`/bookings/${b.id}/${approve ? 'approve' : 'decline'}`, 'POST')
    : call(`/admin/bookings/${b.id}`, 'PATCH', { status: approve ? 'Approved' : 'Cancelled' }), hook);
  return (
    <Card>
      <View style={s.row}><Text style={s.bold}>{b.guest}</Text><Badge t={b.flag || cap(b.status)} k="w" /></View>
      <Text style={s.mu}>{b.listing} - {b.dates}</Text>
      <View style={s.row2}><Btn p t="Approve" onPress={() => go(true)} /><Btn t={host ? 'Decline' : 'Cancel'} onPress={() => go(false)} /></View>
    </Card>
  );
}

// ---------- Guest ----------
// ---------- Date picker (tap to open a calendar) ----------
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const pad = (n) => String(n).padStart(2, '0');
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const parseD = (str) => { const [y, m, d] = str.split('-').map(Number); return new Date(y, m - 1, d); };
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
const pretty = (str) => { const d = parseD(str); return `${MONTHS[d.getMonth()].slice(0, 3)} ${d.getDate()}, ${d.getFullYear()}`; };

function DateField({ label, value, onChange, min, max, clearable, blocked = [] }) {
  const [open, setOpen] = useState(false);
  const [view, setView] = useState(new Date());
  const show = () => {
    const base = value ? parseD(value) : min ? parseD(min) : new Date();
    setView(new Date(base.getFullYear(), base.getMonth(), 1));
    setOpen(true);
  };
  const y = view.getFullYear();
  const m = view.getMonth();
  const cells = [...Array(new Date(y, m, 1).getDay()).fill(null), ...Array.from({ length: new Date(y, m + 1, 0).getDate() }, (_, k) => k + 1)];
  const canPrev = !min || iso(new Date(y, m, 0)) >= min;
  const canNext = !max || iso(new Date(y, m + 1, 1)) <= max;
  return (
    <>
      <TouchableOpacity style={s.input} onPress={show}>
        <Text style={{ color: value ? C.tx : '#999' }}>{value ? pretty(value) : label}</Text>
      </TouchableOpacity>
      <Modal transparent animationType="fade" visible={open} onRequestClose={() => setOpen(false)}>
        <View style={s.overlay}>
          <View style={s.cal}>
            <Text style={s.bold}>{label}</Text>
            <View style={s.row}>
              <TouchableOpacity disabled={!canPrev} onPress={() => setView(new Date(y, m - 1, 1))}><Text style={[s.calNav, !canPrev && { opacity: 0.25 }]}>{'\u2039'}</Text></TouchableOpacity>
              <Text style={s.bold2}>{MONTHS[m]} {y}</Text>
              <TouchableOpacity disabled={!canNext} onPress={() => setView(new Date(y, m + 1, 1))}><Text style={[s.calNav, !canNext && { opacity: 0.25 }]}>{'\u203A'}</Text></TouchableOpacity>
            </View>
            <View style={s.calWeek}>{['S', 'M', 'T', 'W', 'T', 'F', 'S'].map((d, i) => <Text key={i} style={s.calHead}>{d}</Text>)}</View>
            <View style={s.calGrid}>
              {cells.map((d, k) => {
                if (!d) return <View key={k} style={s.calCell} />;
                const v = `${y}-${pad(m + 1)}-${pad(d)}`;
                const occ = blocked.some(([a, b]) => v >= a && v < b); // night already booked
                const off = occ || (min && v < min) || (max && v > max);
                const sel = v === value;
                return (
                  <TouchableOpacity key={k} disabled={off} style={[s.calCell, occ && s.calOcc, sel && s.calSel]} onPress={() => { onChange(v); setOpen(false); }}>
                    <Text style={[{ color: C.tx }, off && { color: '#ccc' }, occ && { color: '#c2321b' }, sel && { color: '#fff', fontWeight: '700' }]}>{d}</Text>
                  </TouchableOpacity>
                );
              })}
            </View>
            {blocked.length ? <Text style={s.mu}>Red dates are already booked.</Text> : null}
            <View style={s.row2}>
              {clearable && value ? <Btn t="Clear" onPress={() => { onChange(''); setOpen(false); }} /> : null}
              <Btn t="Close" onPress={() => setOpen(false)} />
            </View>
          </View>
        </View>
      </Modal>
    </>
  );
}

function Stay({ l, onDone }) {
  const [i, setI] = useState('');
  const [o, setO] = useState('');
  const today = iso(new Date());
  const minIn = l.available_from && l.available_from > today ? l.available_from : today;
  const minOut = iso(addDays(parseD(i || minIn), 1));
  const booked = l.booked || [];
  const nextStart = i ? booked.map((b) => b[0]).filter((a) => a >= i).sort()[0] : undefined; // next booking after your check-in
  const maxOut = [l.available_to, nextStart].filter(Boolean).sort()[0];
  const go = async () => {
    try {
      const d = await call('/bookings', 'POST', { listing_id: l.id, check_in: i, check_out: o });
      Alert.alert('Request sent', d.message); setI(''); setO(''); onDone();
    } catch (e) { Alert.alert('Could not send request', e.message); }
  };
  return (
    <Card>
      <Text style={s.bold}>{l.name}</Text>
      <Text style={s.mu}>${l.price} / night - {l.availability}</Text>
      <DateField label="Check-in date" value={i} min={minIn} max={l.available_to || undefined} blocked={booked} onChange={(v) => { setI(v); setO(''); }} />
      <DateField label="Check-out date" value={o} min={minOut} max={maxOut} onChange={setO} />
      {nextStart ? <Text style={s.mu}>Latest check-out: {pretty(maxOut)} (next booking starts then)</Text> : null}
      <Btn p t="Request booking" onPress={go} />
    </Card>
  );
}
function Stays() {
  const h = useLoad('/listings');
  return (
    <Page title="Find a stay" sub="Check-in 2:00 PM, check-out 11:00 AM." hook={h}>
      {h.data && (h.data[0].length ? h.data[0].map((l) => <Stay key={l.id} l={l} onDone={h.silent} />) : <Empty t="No listings are open right now." />)}
    </Page>
  );
}
function MyBookings() {
  const h = useLoad('/bookings');
  return (
    <Page title="My bookings" sub="Your door code appears once a booking is approved." hook={h}>
      {h.data && (h.data[0].length ? h.data[0].map((b) => (
        <Card key={b.id}>
          <View style={s.row}><Text style={s.bold}>{b.listing}</Text><Badge t={b.flag && b.status === 'Flagged' ? 'Under review' : cap(b.status)} k={kind(b.status)} /></View>
          <Text style={s.mu}>{b.dates}</Text>
          {b.code ? <Text style={s.code}>Door code: {b.code}</Text> : null}
          {['Pending', 'Flagged'].includes(b.status) && (
            <View style={s.row2}><Btn t="Cancel request" onPress={() => ask('Cancel this request?', () => run(() => call(`/bookings/${b.id}`, 'PATCH'), h))} /></View>
          )}
        </Card>
      )) : <Empty t="No bookings yet. Request dates on the Stays tab." />)}
    </Page>
  );
}

// ---------- Host ----------
function Requests() {
  const h = useLoad(['/bookings', '/host/listings']);
  const [bk, ls] = h.data || [[], []];
  const now = new Date();
  const pending = bk.filter((b) => b.status === 'Pending');
  const checkedIn = bk.filter((b) => b.status === 'Approved' && b.access === 'active' && new Date(b.check_in) <= now && new Date(b.check_out) >= now).length;
  return (
    <Page title="Booking requests" sub="Review new requests for your listings." hook={h}>
      {h.data && <>
        <Stats items={[['Pending', pending.length], ['Checked in', checkedIn], ['Low battery', ls.filter((l) => l.battery < 20).length]]} />
        {pending.length ? pending.map((b) => <BookingCard key={b.id} b={b} hook={h} role="Host" />) : <Empty t="No pending requests. New requests will show up here." />}
      </>}
    </Page>
  );
}
function ListingForm({ l, onSave, onCancel }) {
  const [name, setName] = useState(l ? l.name : '');
  const [price, setPrice] = useState(l ? String(l.price) : '');
  const [from, setFrom] = useState(l && l.available_from ? l.available_from : '');
  const [to, setTo] = useState(l && l.available_to ? l.available_to : '');
  const [status, setStatus] = useState(l ? l.status : 'active');
  const save = async () => {
    try {
      const body = { name, price: parseInt(price, 10), available_from: from || null, available_to: to || null, status };
      if (l) await call(`/host/listings/${l.id}`, 'PUT', body); else await call('/host/listings', 'POST', body);
      onSave();
    } catch (e) { Alert.alert('Could not save listing', e.message); }
  };
  return (
    <Card>
      <Text style={s.bold}>{l ? 'Edit listing' : 'Add listing'}</Text>
      <TextInput style={s.input} placeholder="Listing name" value={name} onChangeText={setName} />
      <TextInput style={s.input} placeholder="Price per night (USD)" value={price} onChangeText={setPrice} keyboardType="number-pad" />
      <DateField label="Available from (optional)" value={from} onChange={setFrom} clearable />
      <DateField label="Available until (optional)" value={to} min={from || undefined} onChange={setTo} clearable />
      <View style={s.row2}>
        <Btn t={status === 'active' ? 'Status: Active (tap to pause)' : 'Status: Paused (tap to activate)'} onPress={() => setStatus(status === 'active' ? 'paused' : 'active')} />
      </View>
      <View style={s.row2}><Btn p t={l ? 'Save changes' : 'Add listing'} onPress={save} /><Btn t="Cancel" onPress={onCancel} /></View>
    </Card>
  );
}
function HostListings() {
  const h = useLoad('/host/listings');
  const [adding, setAdding] = useState(false);
  const [editId, setEditId] = useState(null);
  const done = () => { setAdding(false); setEditId(null); h.silent(); };
  const list = h.data ? h.data[0] : [];
  return (
    <Page title="Listings" sub="Add, edit, pause or remove your properties." hook={h}>
      {h.data && <>
        {adding ? <ListingForm onSave={done} onCancel={() => setAdding(false)} /> : <Btn p t="Add listing" onPress={() => { setEditId(null); setAdding(true); }} />}
        <View style={{ height: 12 }} />
        {list.length ? list.map((l) => editId === l.id ? (
          <ListingForm key={l.id} l={l} onSave={done} onCancel={() => setEditId(null)} />
        ) : (
          <Card key={l.id}>
            <View style={s.row}><Text style={s.bold}>{l.name}</Text><Badge t={cap(l.status)} k={l.status === 'active' ? 'ok' : ''} /></View>
            <Text style={s.mu}>${l.price} / night - {l.availability}</Text>
            <View style={s.row2}>
              <Btn t="Edit" onPress={() => { setAdding(false); setEditId(l.id); }} />
              <Btn t="Delete" onPress={() => ask('Delete ' + l.name + '?', () => run(() => call(`/host/listings/${l.id}`, 'DELETE'), h))} />
            </View>
          </Card>
        )) : <Empty t="No listings yet. Add your first property to start receiving requests." />}
      </>}
    </Page>
  );
}
function Doorlock() {
  const h = useLoad('/host/doorlock');
  const d = h.data && h.data[0];
  return (
    <Page title="Doorlock" sub={d && d.listing ? d.listing.name : 'Battery, access, and activity.'} hook={h}>
      {d && !d.listing && <Empty t="Add a listing first. Each listing has its own smart lock." />}
      {d && d.listing && <>
        {d.listing.battery < 20 && (
          <View style={s.alert}>
            <Text style={{ color: '#c2321b', fontWeight: '600' }}>Battery is below 20%. Replace it soon so guests are not locked out.</Text>
            <Btn p t="Mark battery replaced" onPress={() => run(() => call(`/host/doorlock/${d.listing.id}/battery-replaced`, 'POST'), h)} />
          </View>
        )}
        <Stats items={[['Battery', d.listing.battery + '%'], ['Lock', 'Locked']]} />
        <Text style={s.h2}>Guest access</Text>
        {d.access.length ? d.access.map((a) => (
          <Card key={a.id}>
            <View style={s.row}><Text style={s.bold}>{a.guest}</Text><Badge t={a.access === 'active' ? 'Active' : 'Removed'} k={a.access === 'active' ? 'ok' : ''} /></View>
            <Text style={s.mu}>{a.dates}</Text>
            {a.access === 'active' && <View style={s.row2}><Btn t="Remove access" onPress={() => ask('Remove access for ' + a.guest + '?', () => run(() => call(`/host/access/${a.id}/remove`, 'POST'), h))} /></View>}
          </Card>
        )) : <Empty t="No guests have access yet. Approved bookings get a code automatically." />}
        <Text style={s.h2}>Lock event log</Text>
        {d.events.length ? d.events.map((e) => (
          <Card key={e.id}><View style={s.row}><Text style={{ flex: 1 }}>{e.event}</Text><Badge t={cap(e.result)} k={kind(e.result)} /></View><Text style={s.mu}>{e.time}</Text></Card>
        )) : <Empty t="No lock activity yet." />}
        <Text style={s.mu}>You are notified only for repeated failed attempts and battery below 20%.</Text>
      </>}
    </Page>
  );
}

// ---------- Admin ----------
function AdminPending() {
  const h = useLoad(['/admin/metrics', '/bookings']);
  const m = h.data && h.data[0];
  const list = h.data ? h.data[1].filter((b) => b.status === 'Pending') : [];
  return (
    <Page title="Pending bookings" sub="Approve or cancel bookings waiting for review." hook={h}>
      {m && <>
        <Stats items={[['Pending', m.pending], ['Approved today', m.approved_today], ['Cancelled today', m.cancelled_today]]} />
        {list.length ? list.map((b) => <BookingCard key={b.id} b={b} hook={h} role="Admin" />) : <Empty t="Nothing waiting for review." />}
      </>}
    </Page>
  );
}
function Flagged() {
  const h = useLoad('/bookings');
  const list = h.data ? h.data[0].filter((b) => b.status === 'Flagged') : [];
  return (
    <Page title="Flagged bookings" sub="First bookings and last-minute requests need an admin." hook={h}>
      {h.data && (list.length ? list.map((b) => <BookingCard key={b.id} b={b} hook={h} role="Admin" />) : <Empty t="No flagged bookings." />)}
    </Page>
  );
}
function Users() {
  const h = useLoad('/admin/users');
  return (
    <Page title="Users" sub="Disable or enable guest and host accounts." hook={h}>
      {h.data && h.data[0].map((u) => (
        <Card key={u.id}>
          <View style={s.row}><Text style={s.bold}>{u.name}</Text><Badge t={u.active ? 'Active' : 'Disabled'} k={u.active ? 'ok' : 'bad'} /></View>
          <Text style={s.mu}>{u.role} - {u.email}</Text>
          {u.role !== 'Admin' && (
            <View style={s.row2}>
              {u.active
                ? <Btn t="Disable" onPress={() => ask('Disable ' + u.name + '?', () => run(() => call(`/admin/users/${u.id}`, 'PATCH'), h))} />
                : <Btn t="Enable" onPress={() => run(() => call(`/admin/users/${u.id}`, 'PATCH'), h)} />}
            </View>
          )}
        </Card>
      ))}
    </Page>
  );
}

// ---------- Login / register + shell ----------
function Login({ onDone }) {
  const [reg, setReg] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [pw, setPw] = useState('');
  const [err, setErr] = useState('');
  const [busy, setBusy] = useState(false);
  const go = async () => {
    setBusy(true); setErr('');
    try {
      const d = reg ? await call('/register', 'POST', { name, email, password: pw }) : await call('/login', 'POST', { email, password: pw });
      TOKEN = d.token; onDone(d.user);
    } catch (e) { setErr(e.message); }
    setBusy(false);
  };
  const fill = (e, p) => { setReg(false); setEmail(e); setPw(p); };
  return (
    <ScrollView style={s.app} contentContainerStyle={{ padding: 24, flexGrow: 1, justifyContent: 'center' }} keyboardShouldPersistTaps="handled">
      <Text style={s.brand}>Airbnb Check-in System</Text>
      <Text style={s.h1}>{reg ? 'Create a guest account' : 'Log in'}</Text>
      {err ? <Text style={s.err}>{err}</Text> : null}
      {reg && <TextInput style={s.input} placeholder="Full name" value={name} onChangeText={setName} />}
      <TextInput style={s.input} placeholder="Email" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
      <TextInput style={s.input} placeholder={reg ? 'Password (6+ characters)' : 'Password'} value={pw} onChangeText={setPw} secureTextEntry autoCapitalize="none" />
      <Btn p t={busy ? 'Please wait...' : reg ? 'Create account' : 'Log in'} onPress={go} />
      <TouchableOpacity onPress={() => setReg(!reg)} style={{ marginTop: 14 }}><Text style={s.mu}>{reg ? 'Already registered? Log in' : 'New guest? Create an account'}</Text></TouchableOpacity>
      <Text style={[s.mu, { marginTop: 22 }]}>Demo accounts. Tap to fill:</Text>
      <View style={s.row2}>
        <Btn t="Admin" onPress={() => fill('admin@doorkey.demo', 'admin123')} />
        <Btn t="Host" onPress={() => fill('host@doorkey.demo', 'host123')} />
        <Btn t="Guest" onPress={() => fill('maria@doorkey.demo', 'guest123')} />
      </View>
    </ScrollView>
  );
}

const TABS = {
  Guest: [['Stays', Stays], ['My bookings', MyBookings]],
  Host: [['Requests', Requests], ['Listings', HostListings], ['Doorlock', Doorlock]],
  Admin: [['Pending', AdminPending], ['Flagged', Flagged], ['Users', Users]],
};

export default function App() {
  const [user, setUser] = useState(null);
  const [tab, setTab] = useState(0);
  if (!user) return <Login onDone={(u) => { setTab(0); setUser(u); }} />;
  const tabs = TABS[user.role] || TABS.Guest;
  const Screen = tabs[tab][1];
  const out = async () => { try { await call('/logout', 'POST'); } catch (e) {} TOKEN = null; setUser(null); };
  return (
    <View style={s.app}>
      <View style={s.top}>
        <Text style={s.brand}>Check-in</Text>
        <TouchableOpacity onPress={out}><Text style={s.mu}>{user.role}: log out</Text></TouchableOpacity>
      </View>
      <Screen key={tab} />
      <View style={s.nav}>
        {tabs.map(([l], i) => (
          <TouchableOpacity key={l} style={[s.navI, i === tab && s.navOn]} onPress={() => setTab(i)}>
            <Text style={[s.navT, i === tab && { color: C.tx, fontWeight: '700' }]}>{l}</Text>
          </TouchableOpacity>
        ))}
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  app: { flex: 1, backgroundColor: '#fff', paddingTop: Platform.OS === 'ios' ? 54 : (StatusBar.currentHeight || 24) + 8 },
  top: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 21, paddingBottom: 6 },
  brand: { color: C.brand, fontWeight: '700', fontSize: 16 },
  page: { padding: 21, paddingBottom: 120 },
  h1: { fontSize: 24, fontWeight: '700', color: C.tx, marginBottom: 4 },
  h2: { fontSize: 15, fontWeight: '600', color: C.tx, marginTop: 18, marginBottom: 10 },
  sub: { color: C.mu, marginBottom: 16 },
  mu: { color: C.mu, fontSize: 13 },
  bold: { fontWeight: '600', fontSize: 15, color: C.tx, flex: 1 },
  big: { fontSize: 24, fontWeight: '700', color: C.tx },
  err: { backgroundColor: '#fde8e4', color: '#c2321b', padding: 10, borderRadius: 10, marginBottom: 12, overflow: 'hidden' },
  empty: { color: C.mu, borderWidth: 1, borderColor: C.line, borderRadius: 12, padding: 18 },
  card: { borderWidth: 1, borderColor: C.line, borderRadius: 14, padding: 15, marginBottom: 12, gap: 6 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 8 },
  row2: { flexDirection: 'row', gap: 8, marginTop: 8, flexWrap: 'wrap' },
  badge: { paddingHorizontal: 12, paddingVertical: 4, borderRadius: 99 },
  btn: { borderWidth: 1, borderColor: C.line, borderRadius: 8, paddingVertical: 9, paddingHorizontal: 16, alignItems: 'center', backgroundColor: '#fff' },
  btnP: { backgroundColor: '#222', borderColor: '#222' },
  btnT: { fontWeight: '600', color: C.tx },
  input: { borderWidth: 1, borderColor: C.line, borderRadius: 8, paddingHorizontal: 12, paddingVertical: 10, marginVertical: 5, color: C.tx },
  stats: { flexDirection: 'row', gap: 8, marginBottom: 14 },
  stat: { flex: 1, borderWidth: 1, borderColor: C.line, borderRadius: 12, padding: 12 },
  code: { fontSize: 18, fontWeight: '700', color: C.tx, letterSpacing: 1 },
  alert: { backgroundColor: '#fde8e4', borderRadius: 12, padding: 14, gap: 10, marginBottom: 14 },
  nav: { position: 'absolute', left: 21, right: 21, bottom: 22, flexDirection: 'row', backgroundColor: C.side, borderWidth: 1, borderColor: C.line, borderRadius: 16, padding: 6, gap: 4 },
  navI: { flex: 1, alignItems: 'center', paddingVertical: 10, borderRadius: 10 },
  navOn: { backgroundColor: '#ececec' },
  navT: { color: C.mu, fontSize: 14 },
  bold2: { fontWeight: '600', fontSize: 16, color: C.tx },
  overlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'center', padding: 24 },
  cal: { backgroundColor: '#fff', borderRadius: 16, padding: 18, gap: 8 },
  calNav: { fontSize: 30, color: C.tx, paddingHorizontal: 14 },
  calWeek: { flexDirection: 'row' },
  calHead: { width: '14.28%', textAlign: 'center', color: C.mu, fontSize: 12 },
  calGrid: { flexDirection: 'row', flexWrap: 'wrap' },
  calCell: { width: '14.28%', height: 40, alignItems: 'center', justifyContent: 'center', borderRadius: 20 },
  calSel: { backgroundColor: C.brand },
  calOcc: { backgroundColor: '#fde8e4' },
});
