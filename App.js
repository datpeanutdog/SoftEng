import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, RefreshControl, Alert, Platform, StatusBar, ActivityIndicator, Modal, KeyboardAvoidingView, StyleSheet, useWindowDimensions } from 'react-native';
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
  const { width } = useWindowDimensions();
  const desktop = Platform.OS === 'web' && width >= 960;
  return (
    <ScrollView style={s.pageScroll} contentContainerStyle={[s.page, desktop && s.desktopPage]} refreshControl={<RefreshControl refreshing={hook.ref} onRefresh={hook.reload} />}>
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
  const go = async (approve) => {
    try {
      const result = await (host
        ? call(`/bookings/${b.id}/${approve ? 'approve' : 'decline'}`, 'POST')
        : call(`/admin/bookings/${b.id}`, 'PATCH', { status: approve ? 'Approved' : 'Cancelled' }));
      hook.silent();
      if (result.sms && !result.sms.sent) Alert.alert('SMS notification failed', result.sms.message);
    } catch (e) { Alert.alert('Could not complete', e.message); }
  };
  return (
    <Card>
      <View style={s.row}><Text style={s.bold}>{b.guest}</Text><Badge t={b.flag || cap(b.status)} k="w" /></View>
      <Text style={s.mu}>{b.listing} - {b.dates}</Text>
      <Text style={s.mu}>Payment: {b.payment_status === 'verified' ? 'Verified' : b.payment_status === 'pending_verification' ? `Awaiting verification (${b.payment_method || 'unknown'})` : 'Not submitted'}</Text>
      {b.payment_reference ? <Text style={s.mu}>Reference: {b.payment_reference}</Text> : null}
      <View style={s.row2}>
        {b.payment_status === 'verified'
          ? <Btn p t="Approve" onPress={() => go(true)} />
          : <Text style={s.mu}>Payment must be verified by an admin before approval.</Text>}
        <Btn t={host ? 'Decline' : 'Cancel'} onPress={() => go(false)} />
      </View>
    </Card>
  );
}

const TYPES = ['Hotel', 'Airbnb', 'Motel', 'Pension house', 'Resort'];
const peso = (n) => '\u20B1' + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

function Logo({ size = 60 }) {
  const w = size * 0.07;
  return (
    <View style={{ width: size, height: size, borderRadius: size * 0.3, backgroundColor: C.brand, alignItems: 'center', justifyContent: 'center' }}>
      <View style={{ width: size * 0.32, height: size * 0.24, borderWidth: w, borderBottomWidth: 0, borderColor: '#fff', borderTopLeftRadius: size * 0.16, borderTopRightRadius: size * 0.16 }} />
      <View style={{ width: size * 0.5, height: size * 0.34, borderRadius: size * 0.07, backgroundColor: '#fff', alignItems: 'center', justifyContent: 'center', marginTop: -1 }}>
        <View style={{ width: size * 0.09, height: size * 0.09, borderRadius: size * 0.05, backgroundColor: C.brand }} />
        <View style={{ width: size * 0.035, height: size * 0.08, backgroundColor: C.brand, marginTop: -1 }} />
      </View>
    </View>
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

function Stay({ l, onDone, onPayment }) {
  const [i, setI] = useState('');
  const [o, setO] = useState('');
  const [requested, setRequested] = useState(false);
  const today = iso(new Date());
  const minIn = l.available_from && l.available_from > today ? l.available_from : today;
  const minOut = iso(addDays(parseD(i || minIn), 1));
  const booked = l.booked || [];
  const nextStart = i ? booked.map((b) => b[0]).filter((a) => a >= i).sort()[0] : undefined; // next booking after your check-in
  const maxOut = [l.available_to, nextStart].filter(Boolean).sort()[0];
  const go = async () => {
    try {
      const d = await call('/bookings', 'POST', { listing_id: l.id, check_in: i, check_out: o });
      setI(''); setO(''); setRequested(true); onDone();
    } catch (e) { Alert.alert('Could not send request', e.message); }
  };
  return (
    <Card>
      <View style={s.row}><Text style={s.bold}>{l.name}</Text><Badge t={l.type} /></View>
      <Text style={s.mu}>{l.location}, Cagayan de Oro</Text>
      <Text style={s.price}>{peso(l.price)} <Text style={s.mu}>per night</Text></Text>
      <Text style={s.mu}>Available: {l.availability}</Text>
      <DateField label="Check-in date" value={i} min={minIn} max={l.available_to || undefined} blocked={booked} onChange={(v) => { setI(v); setO(''); }} />
      <DateField label="Check-out date" value={o} min={minOut} max={maxOut} onChange={setO} />
      {nextStart ? <Text style={s.mu}>Latest check-out: {pretty(maxOut)} (next booking starts then)</Text> : null}
      <Btn p t="Request booking" onPress={go} />
      {requested && <View style={s.paymentNotice}><Text style={s.paymentNoticeText}>Booking request sent. Submit payment from My bookings so it can be reviewed.</Text><Btn t="Go to My bookings" onPress={onPayment} /></View>}
    </Card>
  );
}
function Stays({ onPayment }) {
  const h = useLoad('/listings');
  return (
    <Page title="Find a stay" sub="Check-in 2:00 PM, check-out 11:00 AM." hook={h}>
      {h.data && (h.data[0].length ? h.data[0].map((l) => <Stay key={l.id} l={l} onDone={h.silent} onPayment={onPayment} />) : <Empty t="No listings are open right now." />)}
    </Page>
  );
}
function PaymentForm({ booking, onSubmitted }) {
  const [method, setMethod] = useState('ewallet');
  const [reference, setReference] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const submit = async () => {
    setBusy(true); setError('');
    try {
      await call(`/bookings/${booking.id}/payment`, 'PUT', {
        payment_method: method,
        payment_reference: reference.trim() || null,
      });
      onSubmitted();
    } catch (e) { setError(e.message); }
    setBusy(false);
  };
  return (
    <View style={s.paymentBox}>
      <Text style={s.bold}>Payment</Text>
      <Text style={s.mu}>Total: {peso(booking.total)}</Text>
      <View style={s.row2}>
        {[
          ['ewallet', 'E-wallet'],
          ['bank_transfer', 'Bank transfer'],
          ['cash', 'Cash'],
        ].map(([value, label]) => (
          <TouchableOpacity key={value} style={[s.chip, method === value && s.chipOn]} onPress={() => setMethod(value)}>
            <Text style={[s.chipT, method === value && { color: '#fff' }]}>{label}</Text>
          </TouchableOpacity>
        ))}
      </View>
      {method === 'cash' ? (
        <Text style={s.mu}>Pay the host in person and request a receipt. The booking stays unapproved until an admin verifies that payment was received.</Text>
      ) : (
        <>
          <Text style={s.mu}>Pay using details provided by the host through a verified channel, then enter your transaction reference. Never share your PIN or OTP.</Text>
          <TextInput style={s.input} placeholder="Transaction reference" value={reference} onChangeText={setReference} />
        </>
      )}
      <Text style={s.paymentSafety}>Payment is manually verified. Do not send money to unverified accounts.</Text>
      {error ? <Text style={s.err}>{error}</Text> : null}
      <Btn p t={busy ? 'Submitting...' : 'Submit payment for verification'} onPress={submit} />
    </View>
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
          <Text style={s.mu}>Total: {peso(b.total)} ({b.nights} {b.nights === 1 ? 'night' : 'nights'})</Text>
          {b.code ? <Text style={s.code}>Door code: {b.code}</Text> : null}
          {b.payment_status !== 'unpaid' && <Text style={s.mu}>Payment: {b.payment_status === 'pending_verification' ? 'Awaiting admin verification' : cap(b.payment_status)}{b.payment_method ? ` via ${b.payment_method.replace('_', ' ')}` : ''}</Text>}
          {b.refund_status === 'due' && <Text style={s.refundNotice}>Refund due: an admin must process this refund manually.</Text>}
          {b.refund_status === 'refunded' && <Text style={s.mu}>Refund marked as processed.</Text>}
          {b.payment_status === 'unpaid' && ['Pending', 'Flagged'].includes(b.status) && <PaymentForm booking={b} onSubmitted={h.silent} />}
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
  const [type, setType] = useState(l ? l.type : 'Hotel');
  const [location, setLocation] = useState(l && l.location ? l.location : '');
  const [price, setPrice] = useState(l ? String(l.price) : '');
  const [from, setFrom] = useState(l && l.available_from ? l.available_from : '');
  const [to, setTo] = useState(l && l.available_to ? l.available_to : '');
  const [status, setStatus] = useState(l ? l.status : 'active');
  const save = async () => {
    try {
      const body = { name, type, location, price: parseInt(price, 10), available_from: from || null, available_to: to || null, status };
      if (l) await call(`/host/listings/${l.id}`, 'PUT', body); else await call('/host/listings', 'POST', body);
      onSave();
    } catch (e) { Alert.alert('Could not save listing', e.message); }
  };
  return (
    <Card>
      <Text style={s.bold}>{l ? 'Edit listing' : 'Add listing'}</Text>
      <TextInput style={s.input} placeholder="Property name" value={name} onChangeText={setName} />
      <Text style={s.mu}>Property type</Text>
      <View style={s.row2}>
        {TYPES.map((t) => (
          <TouchableOpacity key={t} style={[s.chip, type === t && s.chipOn]} onPress={() => setType(t)}>
            <Text style={[s.chipT, type === t && { color: '#fff' }]}>{t}</Text>
          </TouchableOpacity>
        ))}
      </View>
      <TextInput style={s.input} placeholder="Area in Cagayan de Oro (e.g. Carmen)" value={location} onChangeText={setLocation} />
      <TextInput style={s.input} placeholder="Price per night (PHP)" value={price} onChangeText={setPrice} keyboardType="number-pad" />
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
            <Text style={s.mu}>{l.type} - {l.location}, Cagayan de Oro</Text>
            <Text style={s.mu}>{peso(l.price)} per night - {l.availability}</Text>
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
  const [sel, setSel] = useState(null);
  const h = useLoad(['/host/listings', '/host/doorlock' + (sel ? '?listing=' + sel : '')]);
  const ls = h.data ? h.data[0] : [];
  const d = h.data && h.data[1];
  return (
    <Page title="Doorlock" sub={d && d.listing ? d.listing.name : 'Battery, access, and activity.'} hook={h}>
      {d && !d.listing && <Empty t="Add a listing first. Each listing has its own smart lock." />}
      {d && d.listing && <>
        {ls.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 12 }}>
            {ls.map((l) => {
              const on = d.listing.id === l.id;
              return (
                <TouchableOpacity key={l.id} style={[s.chip, on && s.chipOn]} onPress={() => setSel(l.id)}>
                  <Text style={[s.chipT, on && { color: '#fff' }]}>{l.name}</Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        )}
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
function AdminPayments() {
  const h = useLoad('/bookings');
  const items = h.data ? h.data[0].filter((b) => b.payment_status === 'pending_verification' || b.refund_status === 'due') : [];
  return (
    <Page title="Payments & refunds" sub="Verify guest payments before a booking is approved. Process due refunds manually." hook={h}>
      {h.data && (items.length ? items.map((b) => (
        <Card key={b.id}>
          <View style={s.row}><Text style={s.bold}>{b.guest} - {b.listing}</Text><Badge t={b.refund_status === 'due' ? 'Refund due' : 'Verify payment'} k={b.refund_status === 'due' ? 'bad' : 'w'} /></View>
          <Text style={s.mu}>{b.dates} - {peso(b.total)} ({b.nights} {b.nights === 1 ? 'night' : 'nights'})</Text>
          <Text style={s.mu}>Booking: {b.status} - Method: {b.payment_method ? b.payment_method.replace('_', ' ') : 'Not set'}</Text>
          {b.payment_reference ? <Text style={s.mu}>Reference: {b.payment_reference}</Text> : null}
          {b.payment_status === 'pending_verification' && <View style={s.paymentNotice}>
            <Text style={s.paymentSafety}>Confirm the payment independently with the provider or host before marking it verified.</Text>
            <Btn p t="Mark payment verified" onPress={() => run(() => call(`/admin/bookings/${b.id}/payment/verify`, 'POST'), h)} />
          </View>}
          {b.refund_status === 'due' && <View style={s.paymentNotice}>
            <Text style={s.refundNotice}>Confirm the money was received and manually returned before marking this refund complete.</Text>
            <Btn t="Mark refund processed" onPress={() => ask('Confirm that this refund was manually sent?', () => run(() => call(`/admin/bookings/${b.id}/refund/complete`, 'POST'), h))} />
          </View>}
        </Card>
      )) : <Empty t="No payments need verification and no refunds are due." />)}
    </Page>
  );
}
function AdminSms() {
  const h = useLoad('/bookings');
  const failed = h.data ? h.data[0].filter((b) => b.decision_sms_status === 'failed') : [];
  const resend = async (booking) => {
    try {
      const result = await call(`/admin/bookings/${booking.id}/sms/resend`, 'POST');
      h.silent();
      if (!result.sms.sent) Alert.alert('SMS notification failed', result.sms.message);
    } catch (e) { Alert.alert('Could not resend SMS', e.message); }
  };
  return (
    <Page title="SMS notifications" sub="Review and retry decision texts that could not be sent." hook={h}>
      {h.data && (failed.length ? failed.map((b) => (
        <Card key={b.id}>
          <View style={s.row}><Text style={s.bold}>{b.guest} - {b.listing}</Text><Badge t={cap(b.status)} k={kind(b.status)} /></View>
          <Text style={s.mu}>{b.dates} - SMS failed</Text>
          <Btn t="Retry SMS" onPress={() => resend(b)} />
        </Card>
      )) : <Empty t="No failed decision SMS notifications." />)}
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
          {u.phone ? <Text style={s.mu}>{u.phone}</Text> : null}
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
function Login({ onDone, desktop }) {
  const [reg, setReg] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [pw, setPw] = useState('');
  const [show, setShow] = useState(false);
  const [err, setErr] = useState('');
  const [busy, setBusy] = useState(false);
  const go = async () => {
    if (!email.trim() || !pw || (reg && (!name.trim() || !/^9\d{9}$/.test(phone)))) {
      setErr(reg && !/^9\d{9}$/.test(phone) ? 'Enter a 10-digit Philippine mobile number starting with 9.' : 'Please fill in all the fields.');
      return;
    }
    setBusy(true); setErr('');
    try {
      const d = reg
        ? await call('/register', 'POST', { name: name.trim(), email: email.trim(), password: pw, phone: `+63${phone}` })
        : await call('/login', 'POST', { email: email.trim(), password: pw });
      TOKEN = d.token; onDone(d.user);
    } catch (e) { setErr(e.message); }
    setBusy(false);
  };
  return (
    <KeyboardAvoidingView style={[s.app, desktop && s.loginDesktopApp]} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={[s.loginWrap, desktop && s.loginDesktopWrap]} keyboardShouldPersistTaps="handled">
        <View style={s.loginBrandRow}><Logo size={48} /><Text style={s.loginBrand}>LockBay</Text></View>
        <Text style={s.loginTag}>Smart check-in for stays in Cagayan de Oro</Text>
        <View style={[s.loginCard, desktop && s.loginDesktopCard]}>
          <Text style={s.h1}>{reg ? 'Create your account' : 'Welcome back'}</Text>
          <Text style={s.sub}>{reg ? 'Sign up as a guest to book your stay.' : 'Log in to continue.'}</Text>
          {err ? <Text style={s.err}>{err}</Text> : null}
          {reg && <>
            <Text style={s.label}>Full name</Text>
            <TextInput style={s.input} value={name} onChangeText={setName} autoComplete="name" />
            <Text style={s.label}>Philippine mobile number</Text>
            <View style={s.phoneRow}>
              <Text style={s.phonePrefix}>+63</Text>
              <TextInput style={[s.input, s.phoneInput]} value={phone} onChangeText={(value) => setPhone(value.replace(/\D/g, '').slice(0, 10))} keyboardType="phone-pad" autoComplete="tel" placeholder="9XX XXX XXXX" maxLength={10} />
            </View>
          </>}
          <Text style={s.label}>Email</Text>
          <TextInput style={s.input} value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" autoComplete="email" />
          <Text style={s.label}>Password</Text>
          <View style={s.pwRow}>
            <TextInput style={[s.input, { flex: 1, marginVertical: 0, borderWidth: 0 }]} value={pw} onChangeText={setPw} secureTextEntry={!show} autoCapitalize="none" autoComplete="password" />
            <TouchableOpacity onPress={() => setShow(!show)}><Text style={s.link}>{show ? 'Hide' : 'Show'}</Text></TouchableOpacity>
          </View>
          <View style={{ height: 14 }} />
          <Btn p t={busy ? 'Please wait...' : reg ? 'Create account' : 'Log in'} onPress={go} />
          <TouchableOpacity onPress={() => { setReg(!reg); setErr(''); }} style={{ marginTop: 16, alignItems: 'center' }}>
            <Text style={s.mu}>{reg ? 'Already have an account? ' : 'New here? '}<Text style={s.link}>{reg ? 'Log in' : 'Create an account'}</Text></Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const TABS = {
  Guest: [['Stays', Stays], ['My bookings', MyBookings]],
  Host: [['Requests', Requests], ['Listings', HostListings], ['Doorlock', Doorlock]],
  Admin: [['Pending', AdminPending], ['Flagged', Flagged], ['Payments', AdminPayments], ['SMS', AdminSms], ['Users', Users]],
};

export default function App() {
  const [user, setUser] = useState(null);
  const [tab, setTab] = useState(0);
  const { width } = useWindowDimensions();
  const desktop = Platform.OS === 'web' && width >= 960;
  if (!user) return <Login desktop={desktop} onDone={(u) => { setTab(0); setUser(u); }} />;
  const tabs = TABS[user.role] || TABS.Guest;
  const Screen = tabs[tab][1];
  const out = async () => { try { await call('/logout', 'POST'); } catch (e) {} TOKEN = null; setUser(null); };
  return (
    <View style={[s.app, desktop && s.desktopApp]}>
      {desktop ? (
        <View style={s.desktopLayout}>
          <View style={s.sidebar}>
            <View style={s.brandRow}><Logo size={36} /><Text style={s.brandName}>LockBay</Text></View>
            <Text style={s.sidebarLabel}>WORKSPACE</Text>
            <View style={s.desktopNav}>
              {tabs.map(([l], i) => (
                <TouchableOpacity key={l} style={[s.desktopNavI, i === tab && s.desktopNavOn]} onPress={() => setTab(i)}>
                  <Text style={[s.desktopNavT, i === tab && s.desktopNavTOn]}>{l}</Text>
                </TouchableOpacity>
              ))}
            </View>
            <View style={s.sidebarAccount}>
              <Text style={s.sidebarName}>{user.name || user.role}</Text>
              <Text style={s.mu}>{user.role} account</Text>
              <TouchableOpacity onPress={out} style={s.logoutButton}>
                <Text style={s.logoutText}>Log out</Text>
              </TouchableOpacity>
            </View>
          </View>
          <View style={s.desktopMain}>
            <View style={s.desktopHeader}>
              <View>
                <Text style={s.desktopEyebrow}>LOCKBAY / {user.role.toUpperCase()}</Text>
                <Text style={s.desktopHeading}>{tabs[tab][0]}</Text>
              </View>
              <Text style={s.desktopStatus}>Smart stay management</Text>
            </View>
            <View style={s.desktopScreen}>
              <Screen key={tab} {...(user.role === 'Guest' && tab === 0 ? { onPayment: () => setTab(1) } : {})} />
            </View>
          </View>
        </View>
      ) : (
        <>
          <View style={s.top}>
            <View style={s.brandRow}><Logo size={30} /><Text style={s.brandName}>LockBay</Text></View>
            <TouchableOpacity onPress={out}><Text style={s.mu}>{user.role}: log out</Text></TouchableOpacity>
          </View>
          <Screen key={tab} {...(user.role === 'Guest' && tab === 0 ? { onPayment: () => setTab(1) } : {})} />
          <View style={s.nav}>
            {tabs.map(([l], i) => (
              <TouchableOpacity key={l} style={[s.navI, i === tab && s.navOn]} onPress={() => setTab(i)}>
                <Text style={[s.navT, i === tab && { color: C.tx, fontWeight: '700' }]}>{l}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </>
      )}
    </View>
  );
}

const s = StyleSheet.create({
  app: { flex: 1, backgroundColor: '#fff', paddingTop: Platform.OS === 'web' ? 0 : Platform.OS === 'ios' ? 54 : (StatusBar.currentHeight || 24) + 8 },
  desktopApp: { paddingTop: 0, backgroundColor: C.side },
  desktopLayout: { flex: 1, flexDirection: 'row' },
  sidebar: { width: 252, backgroundColor: '#fff', borderRightWidth: 1, borderRightColor: C.line, paddingHorizontal: 22, paddingTop: 28, paddingBottom: 20 },
  sidebarLabel: { color: C.mu, fontSize: 11, fontWeight: '700', letterSpacing: 1.2, marginTop: 48, marginBottom: 12 },
  desktopNav: { gap: 5 },
  desktopNavI: { borderRadius: 10, paddingHorizontal: 13, paddingVertical: 12 },
  desktopNavOn: { backgroundColor: '#f2f2f2' },
  desktopNavT: { color: C.mu, fontSize: 14, fontWeight: '500' },
  desktopNavTOn: { color: C.tx, fontWeight: '700' },
  sidebarAccount: { marginTop: 'auto', borderTopWidth: 1, borderTopColor: C.line, paddingTop: 18 },
  sidebarName: { color: C.tx, fontSize: 14, fontWeight: '700', marginBottom: 4 },
  logoutButton: { marginTop: 12, borderWidth: 1, borderColor: C.line, borderRadius: 8, paddingVertical: 9, alignItems: 'center' },
  logoutText: { color: C.tx, fontSize: 13, fontWeight: '600' },
  desktopMain: { flex: 1, minWidth: 0 },
  desktopHeader: { minHeight: 82, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 36, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: C.line },
  desktopEyebrow: { color: C.mu, fontSize: 10, fontWeight: '700', letterSpacing: 1.1, marginBottom: 4 },
  desktopHeading: { color: C.tx, fontSize: 18, fontWeight: '700' },
  desktopStatus: { color: C.mu, fontSize: 13 },
  desktopScreen: { flex: 1 },
  pageScroll: { flex: 1 },
  top: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 21, paddingBottom: 6 },
  brand: { color: C.brand, fontWeight: '700', fontSize: 16 },
  page: { padding: 21, paddingBottom: 120 },
  desktopPage: { width: '100%', maxWidth: 1120, alignSelf: 'center', padding: 36, paddingBottom: 48 },
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
  paymentBox: { backgroundColor: C.side, borderWidth: 1, borderColor: C.line, borderRadius: 12, padding: 14, gap: 8, marginTop: 8 },
  paymentNotice: { backgroundColor: '#f7f7f7', borderRadius: 10, padding: 12, gap: 8, marginTop: 8 },
  paymentNoticeText: { color: C.tx, fontSize: 13 },
  paymentSafety: { color: '#8a5a00', fontSize: 12 },
  refundNotice: { color: '#c2321b', fontSize: 13, fontWeight: '600' },
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
  price: { fontSize: 18, fontWeight: '700', color: C.tx },
  label: { color: C.tx, fontWeight: '600', fontSize: 13, marginTop: 8 },
  chip: { borderWidth: 1, borderColor: C.line, borderRadius: 99, paddingHorizontal: 14, paddingVertical: 8, marginRight: 8 },
  chipOn: { backgroundColor: '#222', borderColor: '#222' },
  chipT: { color: C.tx, fontWeight: '600', fontSize: 13 },
  loginWrap: { flexGrow: 1, justifyContent: 'center', padding: 24 },
  loginDesktopApp: { backgroundColor: C.side },
  loginDesktopWrap: { alignItems: 'center', paddingVertical: 48 },
  loginDesktopCard: { width: '100%', maxWidth: 460, backgroundColor: '#fff' },
  logo: { width: 60, height: 60, borderRadius: 18, backgroundColor: C.brand, alignItems: 'center', justifyContent: 'center', alignSelf: 'center' },
  door: { width: 22, height: 32, borderRadius: 5, backgroundColor: '#fff', justifyContent: 'center', alignItems: 'flex-end', paddingRight: 4 },
  knob: { width: 5, height: 5, borderRadius: 3, backgroundColor: C.brand },
  loginBrandRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 12, marginBottom: 2 },
  loginBrand: { fontSize: 32, fontWeight: '800', color: C.tx, letterSpacing: -0.5 },
  brandRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  brandName: { fontSize: 18, fontWeight: '800', color: C.tx, letterSpacing: -0.3 },
  loginTag: { textAlign: 'center', color: C.mu, marginTop: 4, marginBottom: 22 },
  loginCard: { borderWidth: 1, borderColor: C.line, borderRadius: 18, padding: 20 },
  phoneRow: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: C.line, borderRadius: 8, paddingLeft: 12, marginVertical: 5 },
  phonePrefix: { color: C.tx, fontWeight: '600' },
  phoneInput: { flex: 1, marginVertical: 0, borderWidth: 0 },
  pwRow: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: C.line, borderRadius: 8, paddingRight: 12, marginVertical: 5 },
  link: { color: C.brand, fontWeight: '600', fontSize: 13 },
});
