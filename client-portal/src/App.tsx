import React, { useState, useEffect, useMemo, useRef } from 'react';
import { 
  LayoutDashboard, 
  CreditCard, 
  CheckSquare, 
  BarChart3, 
  Plus, 
  Search, 
  Bell, 
  Download, 
  CheckCircle2, 
  AlertCircle, 
  Clock, 
  ChevronRight, 
  TrendingUp, 
  X,
  ArrowUpDown,
  FileText,
  Upload,
  Paperclip,
  Trash2,
  Users,
  MessageSquare,
  Sparkles,
  RefreshCw,
  Sliders,
  Check,
  Edit2,
  Settings,
  ShieldAlert,
  User,
  PlusCircle,
  FilePlus,
  Coins,
  Cpu,
  ShoppingBag,
  BookOpen,
  Layers,
  Wrench,
  DollarSign,
  Wallet,
  UserCheck,
  Percent,
  Activity
} from 'lucide-react';

import { initializeApp } from 'firebase/app';
import { 
  getAuth, 
  signInAnonymously, 
  signInWithCustomToken, 
  onAuthStateChanged 
} from 'firebase/auth';
import { 
  getFirestore, 
  doc, 
  onSnapshot, 
  collection, 
  setDoc,
  updateDoc,
  addDoc,
  deleteDoc 
} from 'firebase/firestore';

let db = null;
let auth = null;
let appId = 'default-smma-portal';

try {
  if (typeof __firebase_config !== 'undefined') {
    const firebaseConfig = JSON.parse(__firebase_config);
    const app = initializeApp(firebaseConfig);
    auth = getAuth(app);
    db = getFirestore(app);
    appId = typeof __app_id !== 'undefined' ? __app_id : 'default-smma-portal';
  }
} catch (error) {
  console.warn("Firebase initialization skipped. Using advanced memory state client-side.", error);
}

const INITIAL_DELIVERABLES = [
  { id: 'deliv-1', name: 'Draft 4x UGC Blogs for SEO optimization', assignedTo: 'Zack Media', status: 'In Progress', type: 'SEO Blog', dueDate: '2026-07-08' },
  { id: 'deliv-2', name: 'Design Content Calendar for FB & Instagram', assignedTo: 'Sarah Design', status: 'Done', type: 'Design', dueDate: '2026-07-02' },
  { id: 'deliv-3', name: 'Configure Pinterest account setup and boards', assignedTo: 'Dylan Doe', status: 'To Do', type: 'Setup', dueDate: '2026-07-12' },
  { id: 'deliv-4', name: 'Deploy Weekly Targeted Ads Campaign (Meta & Google)', assignedTo: 'John PM', status: 'In Progress', type: 'Paid Ads', dueDate: '2026-07-05' },
  { id: 'deliv-5', name: 'Execute 2-hour Daily Engagement & outreach', assignedTo: 'Dylan Doe', status: 'Done', type: 'Engagement', dueDate: '2026-06-28' }
];

const INITIAL_CLIENT_REQUESTS = [
  { id: 'req-1', title: 'Ad copy revision for Eid Campaign', category: 'Task Assignment', status: 'Open', priority: 'High', date: '2026-06-28', description: 'Please tweak the Hook variations for the FB story ads to focus more on the flat 20% discount offer.', adminNotes: '' },
  { id: 'req-2', title: 'Billing Issue: Charge on 15th was double-routed', category: 'Support Ticket', status: 'Open', priority: 'Critical', date: '2026-06-27', description: 'The payment system debited twice. Please review the payment gateway log and refund the surplus.', adminNotes: 'Verified duplicate payment reference. Gateway refund initiated.' }
];

const INITIAL_PACKAGES = [
  {
    id: 'pkg-1',
    name: 'Package 1: Platinum Omnipresence',
    price: 65000,
    limits: { posts: 25, stories: 30, reels: 10 },
    features: [
      '25 posts per month',
      '30 stories',
      '10 reels/videos',
      'Content Calendar',
      'Hashtag Research',
      '2 hour Daily Engagement',
      'Elegant Catchy Graphic Designs',
      'Monthly Report',
      'YouTube SEO',
      'Facebook and Instagram Targeted Ads',
      'Google Ads',
      'Website/Store Management',
      'Pinterest account setup and management',
      '4x UGC Blogs a month for SEO',
      'All social media platform profile creation'
    ]
  },
  {
    id: 'pkg-2',
    name: 'Package 2: Professional Growth',
    price: 55000,
    limits: { posts: 20, stories: 25, reels: 7 },
    features: [
      '20 posts per month',
      '25 stories',
      '7 reels/videos',
      'Content Calendar',
      'Hashtag Research',
      '2 hour Daily Engagement',
      'Elegant Catchy Graphic Designs',
      'Monthly Report',
      'Facebook and Instagram Targeted Ads',
      'Website/Store Management',
      '2 x UGC Blogs a month for SEO',
      'All social media platform profile creation'
    ]
  },
  {
    id: 'pkg-3',
    name: 'Package 3: Essential Starter',
    price: 45000,
    limits: { posts: 15, stories: 15, reels: 5 },
    features: [
      '15 posts per month',
      '15 stories',
      '5 reels/videos',
      'Content Calendar',
      'Hashtag Research',
      '2 hour Daily Engagement',
      'Elegant Catchy Graphic Designs',
      'Monthly Report',
      'Facebook and Instagram Targeted Ads',
      'All social media platform profile creation'
    ]
  }
];

const INITIAL_INVOICES = [
  { id: 'inv-1', number: 'INV-2026-006', date: '2026-06-15', amount: 55000, status: 'Paid', note: 'Standard Retainer Growth Package' },
  { id: 'inv-2', number: 'INV-2026-005', date: '2026-05-15', amount: 55000, status: 'Paid', note: 'Standard Retainer Growth Package' },
  { id: 'inv-3', number: 'INV-2026-004', date: '2026-04-15', amount: 45000, status: 'Paid', note: 'Essential Starter Retainer Package' },
  { id: 'inv-4', number: 'INV-2026-003', date: '2026-03-15', amount: 45000, status: 'Pending', note: 'Essential Starter Retainer Package' }
];

const INITIAL_LEDGER = [
  { date: 'Jun 17, 2026', type: 'Invoice Settled (Pkg 2)', amount: 55000, balance: 110000 },
  { date: 'May 16, 2026', type: 'Invoice Settled (Pkg 2)', amount: 55000, balance: 55000 },
  { date: 'Apr 15, 2026', type: 'Starting Retainer Deposit', amount: 45000, balance: 0 }
];

const INITIAL_BRAND2SOCIAL_ATTACHMENTS = [
  { id: 'att-1', fileName: 'brand2social_weekly_analytics_jun22.pdf', uploadDate: '2026-06-22', fileSize: '2.4 MB', type: 'pdf', uploadedBy: 'Agency PM' },
  { id: 'att-2', fileName: 'instagram_follower_growth_matrix.csv', uploadDate: '2026-06-15', fileSize: '840 KB', type: 'csv', uploadedBy: 'Auto-Sync' }
];

const INITIAL_PLATFORM_AD_DATA = {
  'Meta Ads': {
    spend: 18500,
    impressions: 112450,
    clicks: 8230,
    conversions: 245,
    chartData: [210, 240, 290, 310, 390, 480, 520, 610, 580, 680, 710, 850]
  },
  'Google Ads': {
    spend: 22000,
    impressions: 94500,
    clicks: 11400,
    conversions: 310,
    chartData: [400, 450, 420, 490, 510, 550, 610, 590, 640, 720, 780, 890]
  },
  'TikTok Ads': {
    spend: 14500,
    impressions: 195000,
    clicks: 14200,
    conversions: 188,
    chartData: [150, 180, 210, 260, 310, 340, 420, 450, 490, 520, 580, 630]
  }
};

const INITIAL_ADDONS = [
  { id: 'add-1', name: 'Branding Booster (10 custom posts)', type: 'Branding / Graphics', price: 15000, status: 'In Progress', progress: 50, metrics: '5 / 10 posts delivered' },
  { id: 'add-2', name: 'Elite Video Production (3 4K reels)', type: 'Video Production', price: 30000, status: 'Completed', progress: 100, metrics: '3 / 3 videos delivered' },
  { id: 'add-3', name: 'Ad Account Expansion Setup', type: 'Ad Campaign Services', price: 12000, status: 'Approved & Scheduled', progress: 0, metrics: 'Awaiting launch call' }
];

const INITIAL_CUSTOM_TASKS = [
  { id: 'ct-1', title: 'Corporate Ebook Design & Publishing', category: 'Booklet / Ebook Generation', price: 25000, status: 'In Progress', progress: 65, assignedTo: 'Sarah Design', description: 'Create 15-page interactive layout with custom brand colors and downloadable PDF integration.' },
  { id: 'ct-2', title: 'Website Redesign on Shopify', category: 'Software Development', price: 80000, status: 'Awaiting Client Approval', progress: 0, assignedTo: 'Zack Tech PM', description: 'Complete overhaul of landing page, responsive cart layout, and secure Pakistani bank payment routes configuration.' },
  { id: 'ct-3', title: 'Embroidered Trucker Caps & Ceramic Mugs (Qty: 100)', category: 'Merchandise Printing', price: 35000, status: 'Awaiting Quote', progress: 0, assignedTo: 'Unassigned', description: 'Deploy vector logos for physical production runs. High quality embroidery finish.' }
];

export default function App() {
  // Roles list: client, pm (Project Manager), finance (Finance Manager), admin (Super Admin)
  const [currentRole, setCurrentRole] = useState('client'); 
  const [activeTab, setActiveTab] = useState('dashboard'); 
  const [user, setUser] = useState(null);
  const [toast, setToast] = useState(null);

  const [packages, setPackages] = useState(INITIAL_PACKAGES);
  const [deliverables, setDeliverables] = useState(INITIAL_DELIVERABLES);
  const [clientRequests, setClientRequests] = useState(INITIAL_CLIENT_REQUESTS);
  const [invoices, setInvoices] = useState(INITIAL_INVOICES);
  const [ledger, setLedger] = useState(INITIAL_LEDGER);
  const [brand2socialAttachments, setBrand2socialAttachments] = useState(INITIAL_BRAND2SOCIAL_ATTACHMENTS);
  const [platformAdData, setPlatformAdData] = useState(INITIAL_PLATFORM_AD_DATA);
  const [addons, setAddons] = useState(INITIAL_ADDONS);
  const [customTasks, setCustomTasks] = useState(INITIAL_CUSTOM_TASKS);

  const [activePlanId, setActivePlanId] = useState('pkg-2'); 
  const [socialProgress, setSocialProgress] = useState({
    postsCompleted: 14,
    storiesCompleted: 19,
    reelsCompleted: 5,
    followersGained: 1420,
    totalLikes: 8740,
    brandMentions: 112
  });

  const [searchQuery, setSearchQuery] = useState('');
  const [platformSelected, setPlatformSelected] = useState('Meta Ads');
  const [invoiceFilterStatus, setInvoiceFilterStatus] = useState('All');
  const [invoiceSortDir, setInvoiceSortDir] = useState('desc');
  const fileInputRef = useRef(null);

  // Modals & Dynamic Editors
  const [isRequestModalOpen, setIsRequestModalOpen] = useState(false);
  const [isInvoiceModalOpen, setIsInvoiceModalOpen] = useState(false);
  const [activeInvoiceToPay, setActiveInvoiceToPay] = useState(null);
  const [isUpgradeModalOpen, setIsUpgradeModalOpen] = useState(false);
  const [editingPackage, setEditingPackage] = useState(null);
  const [isEditPackageModalOpen, setIsEditPackageModalOpen] = useState(false);
  const [isAddonModalOpen, setIsAddonModalOpen] = useState(false);
  const [isCustomTaskModalOpen, setIsCustomTaskModalOpen] = useState(false);
  
  // NEW: Project Manager's Verbal Request Modal
  const [isVerbalRequestModalOpen, setIsVerbalRequestModalOpen] = useState(false);

  // NEW: Finance Manager's Invoice Generation State
  const [selectedVerbalForInvoice, setSelectedVerbalForInvoice] = useState(null);
  const [financeInvoicePrice, setFinanceInvoicePrice] = useState(25000);
  const [financeInvoiceNum, setFinanceInvoiceNum] = useState('');

  // Input states for new client custom task
  const [newCTTitle, setNewCTTitle] = useState('');
  const [newCTCategory, setNewCTCategory] = useState('Merchandise Printing');
  const [newCTDesc, setNewCTDesc] = useState('');

  // Input states for Project Manager Verbal Project Addition
  const [verbalTitle, setVerbalTitle] = useState('');
  const [verbalCategory, setVerbalCategory] = useState('Software Development');
  const [verbalDesc, setVerbalDesc] = useState('');

  // Input states for new client support/request task
  const [newRequestTitle, setNewRequestTitle] = useState('');
  const [newRequestCategory, setNewRequestCategory] = useState('Task Assignment');
  const [newRequestPriority, setNewRequestPriority] = useState('Medium');
  const [newRequestDesc, setNewRequestDesc] = useState('');

  // Form inputs for Admin adding new deliverables
  const [newDelivName, setNewDelivName] = useState('');
  const [newDelivType, setNewDelivType] = useState('Design');
  const [newDelivAssignee, setNewDelivAssignee] = useState('Zack Media');
  const [newDelivDueDate, setNewDelivDueDate] = useState('2026-07-15');
  const [newDelivStatus, setNewDelivStatus] = useState('To Do');

  // Form inputs for Admin invoice creation
  const [newInvNum, setNewInvNum] = useState('');
  const [newInvAmount, setNewInvAmount] = useState(55000);
  const [newInvStatus, setNewInvStatus] = useState('Pending');
  const [newInvNote, setNewInvNote] = useState('');

  // Input states for Admin/Finance managing Custom Tasks quotes
  const [selectedTaskForQuote, setSelectedTaskForQuote] = useState(null);
  const [assignQuotePrice, setAssignQuotePrice] = useState(0);
  const [assignQuoteStaff, setAssignQuoteStaff] = useState('Zack PM');

  const [selectedRequestForNote, setSelectedRequestForNote] = useState(null);
  const [tempNoteText, setTempNoteText] = useState('');

  const activePlan = useMemo(() => {
    return packages.find(pkg => pkg.id === activePlanId) || packages[1];
  }, [packages, activePlanId]);

  const triggerToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const handleRoleToggle = (role) => {
    setCurrentRole(role);
    if (role === 'admin') {
      setActiveTab('admin-overview');
    } else if (role === 'pm') {
      setActiveTab('pm-overview');
    } else if (role === 'finance') {
      setActiveTab('fin-overview');
    } else {
      setActiveTab('dashboard');
    }
    triggerToast(`Workspace shifted to: ${role.toUpperCase()}`, 'info');
  };

  useEffect(() => {
    if (!db || !auth) return;

    const initAuth = async () => {
      try {
        if (typeof __initial_auth_token !== 'undefined' && __initial_auth_token) {
          await signInWithCustomToken(auth, __initial_auth_token);
        } else {
          await signInAnonymously(auth);
        }
      } catch (err) {
        console.error("Firebase authentication error:", err);
      }
    };
    initAuth();

    const unsubscribeAuth = onAuthStateChanged(auth, (currentUser) => {
      setUser(currentUser);
      if (currentUser) {
        triggerToast("Connected safely to Cloud Sync Pipeline.", "info");
      }
    });

    return () => unsubscribeAuth();
  }, []);

  useEffect(() => {
    if (!db || !user) return;

    const packagesCol = collection(db, 'artifacts', appId, 'users', user.uid, 'packages');
    const delivCol = collection(db, 'artifacts', appId, 'users', user.uid, 'deliverables');
    const reqsCol = collection(db, 'artifacts', appId, 'users', user.uid, 'requests');
    const invCol = collection(db, 'artifacts', appId, 'users', user.uid, 'invoices');
    const attsCol = collection(db, 'artifacts', appId, 'users', user.uid, 'brand2social');
    const addonsCol = collection(db, 'artifacts', appId, 'users', user.uid, 'addons');
    const customTasksCol = collection(db, 'artifacts', appId, 'users', user.uid, 'customTasks');

    const unsubPackages = onSnapshot(packagesCol, (snap) => {
      if (!snap.empty) setPackages(snap.docs.map(d => ({ id: d.id, ...d.data() })));
      else packages.forEach(p => setDoc(doc(packagesCol, p.id), p));
    });

    const unsubDeliv = onSnapshot(delivCol, (snap) => {
      if (!snap.empty) setDeliverables(snap.docs.map(d => ({ id: d.id, ...d.data() })));
      else deliverables.forEach(d => setDoc(doc(delivCol, d.id), d));
    });

    const unsubReqs = onSnapshot(reqsCol, (snap) => {
      if (!snap.empty) setClientRequests(snap.docs.map(d => ({ id: d.id, ...d.data() })));
      else clientRequests.forEach(r => setDoc(doc(reqsCol, r.id), r));
    });

    const unsubInv = onSnapshot(invCol, (snap) => {
      if (!snap.empty) setInvoices(snap.docs.map(d => ({ id: d.id, ...d.data() })));
      else invoices.forEach(i => setDoc(doc(invCol, i.id), i));
    });

    const unsubAtts = onSnapshot(attsCol, (snap) => {
      if (!snap.empty) setBrand2socialAttachments(snap.docs.map(d => ({ id: d.id, ...d.data() })));
      else brand2socialAttachments.forEach(a => setDoc(doc(attsCol, a.id), a));
    });

    const unsubAddons = onSnapshot(addonsCol, (snap) => {
      if (!snap.empty) setAddons(snap.docs.map(d => ({ id: d.id, ...d.data() })));
      else addons.forEach(a => setDoc(doc(addonsCol, a.id), a));
    });

    const unsubCustomTasks = onSnapshot(customTasksCol, (snap) => {
      if (!snap.empty) setCustomTasks(snap.docs.map(d => ({ id: d.id, ...d.data() })));
      else customTasks.forEach(c => setDoc(doc(customTasksCol, c.id), c));
    });

    return () => {
      unsubPackages();
      unsubDeliv();
      unsubReqs();
      unsubInv();
      unsubAtts();
      unsubAddons();
      unsubCustomTasks();
    };
  }, [user]);

  const handleCreateVerbalRequest = async (e) => {
    e.preventDefault();
    if (!verbalTitle.trim()) return;

    const verbalTask = {
      id: `ct-${Date.now()}`,
      title: verbalTitle,
      category: verbalCategory,
      price: 0, 
      status: 'Awaiting Invoice', // Awaiting Invoice Generation by Finance
      progress: 0,
      assignedTo: 'Project Manager Verbal Add',
      description: verbalDesc || 'Client requested this verbally. Finance needs to issue invoice to start.',
      isVerbalRequest: true
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'customTasks', verbalTask.id), verbalTask);
    } else {
      setCustomTasks(prev => [verbalTask, ...prev]);
    }

    setVerbalTitle('');
    setVerbalDesc('');
    setIsVerbalRequestModalOpen(false);
    triggerToast(`Client verbal task added. Forwarded to Finance Manager for Invoice Generation.`);
  };

  const handleFinanceGenerateCustomInvoice = async (e) => {
    e.preventDefault();
    if (!selectedVerbalForInvoice) return;

    const verbalTask = customTasks.find(c => c.id === selectedVerbalForInvoice);
    if (!verbalTask) return;

    const invoiceNum = financeInvoiceNum || `INV-VERB-${Date.now().toString().slice(-4)}`;
    const invoiceId = `inv-${Date.now()}`;

    // 1. Create linked invoice
    const newInvoice = {
      id: invoiceId,
      number: invoiceNum,
      date: new Date().toISOString().split('T')[0],
      amount: Number(financeInvoicePrice),
      status: 'Pending',
      note: `Custom Invoice for PM Verbal Task: ${verbalTask.title}`
    };

    // 2. Update the custom task with price and status
    const updatedTask = {
      ...verbalTask,
      price: Number(financeInvoicePrice),
      status: 'Awaiting Client Payment'
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'customTasks', selectedVerbalForInvoice), updatedTask);
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'invoices', invoiceId), newInvoice);
    } else {
      setCustomTasks(prev => prev.map(c => c.id === selectedVerbalForInvoice ? updatedTask : c));
      setInvoices(prev => [newInvoice, ...prev]);
    }

    setSelectedVerbalForInvoice(null);
    setFinanceInvoiceNum('');
    triggerToast(`Custom invoice ${invoiceNum} generated & sent to client dashboard for approval.`);
  };

  const handleCreateRequest = async (e) => {
    e.preventDefault();
    if (!newRequestTitle.trim()) return;

    const newRequest = {
      id: `req-${Date.now()}`,
      title: newRequestTitle,
      category: newRequestCategory, 
      status: 'Open',
      priority: newRequestPriority,
      date: new Date().toISOString().split('T')[0],
      description: newRequestDesc || 'No details provided.',
      adminNotes: ''
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'requests', newRequest.id), newRequest);
    } else {
      setClientRequests(prev => [newRequest, ...prev]);
    }

    setNewRequestTitle('');
    setNewRequestDesc('');
    setIsRequestModalOpen(false);
    triggerToast(`Assigned new ${newRequestCategory} to agency queue.`);
  };

  const handleCreateCustomTaskRequest = async (e) => {
    e.preventDefault();
    if (!newCTTitle.trim()) return;

    const newCT = {
      id: `ct-${Date.now()}`,
      title: newCTTitle,
      category: newCTCategory,
      price: 0, 
      status: 'Awaiting Quote',
      progress: 0,
      assignedTo: 'Unassigned',
      description: newCTDesc || 'No details provided.'
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'customTasks', newCT.id), newCT);
    } else {
      setCustomTasks(prev => [newCT, ...prev]);
    }

    setNewCTTitle('');
    setNewCTDesc('');
    setIsCustomTaskModalOpen(false);
    triggerToast(`Custom task requested! PM Zack will formulate a budget quote shortly.`);
  };

  const handleClientApproveCustomProject = async (projId) => {
    const proj = customTasks.find(c => c.id === projId);
    if (!proj) return;

    const updatedTask = {
      ...proj,
      status: 'In Progress'
    };

    // Auto-create ledger entry & invoice for the custom approved project pricing
    const invId = `inv-${Date.now()}`;
    const newInv = {
      id: invId,
      number: `INV-PROJ-${Date.now().toString().slice(-4)}`,
      date: new Date().toISOString().split('T')[0],
      amount: proj.price,
      status: 'Pending',
      note: `Payment for Custom Approved Project: ${proj.title}`
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'customTasks', projId), updatedTask);
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'invoices', invId), newInv);
    } else {
      setCustomTasks(prev => prev.map(c => c.id === projId ? updatedTask : c));
      setInvoices(prev => [newInv, ...prev]);
    }

    triggerToast(`Custom project "${proj.title}" approved! Settle Invoice ${newInv.number} to begin production.`);
  };

  const handleUpdateStatus = async (requestId, currentStatus) => {
    const nextStatus = currentStatus === 'Open' ? 'Resolved' : 'Open';
    if (db && user) {
      await updateDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'requests', requestId), { status: nextStatus });
    } else {
      setClientRequests(prev => prev.map(r => r.id === requestId ? { ...r, status: nextStatus } : r));
    }
    triggerToast(`Status marked as ${nextStatus}`);
  };

  const handlePayInvoice = async (invoiceId) => {
    if (db && user) {
      await updateDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'invoices', invoiceId), { status: 'Paid' });
    } else {
      setInvoices(prev => prev.map(inv => inv.id === invoiceId ? { ...inv, status: 'Paid' } : inv));
    }

    const selectedInv = invoices.find(inv => inv.id === invoiceId);
    if (selectedInv) {
      const newLedgerLog = {
        date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
        type: `Retainer Invoice Settled (${selectedInv.number})`,
        amount: selectedInv.amount,
        balance: ledger.length > 0 ? ledger[0].balance + selectedInv.amount : selectedInv.amount
      };
      setLedger(prev => [newLedgerLog, ...prev]);

      // If this was a paid verbal request, update its status to 'In Progress'
      const matchedTask = customTasks.find(c => c.price === selectedInv.amount && (c.status === 'Awaiting Client Payment' || c.status === 'Awaiting Quote'));
      if (matchedTask) {
        const updatedTask = { ...matchedTask, status: 'In Progress' };
        if (db && user) {
          await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'customTasks', matchedTask.id), updatedTask);
        } else {
          setCustomTasks(prev => prev.map(t => t.id === matchedTask.id ? updatedTask : t));
        }
      }
    }

    setIsInvoiceModalOpen(false);
    triggerToast("Retainer checkout completed! Banking balance cleared successfully.");
  };

  const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const mockFileRecord = {
      id: `att-${Date.now()}`,
      fileName: file.name,
      uploadDate: new Date().toISOString().split('T')[0],
      fileSize: `${(file.size / (1024 * 1024)).toFixed(2)} MB`,
      type: file.name.split('.').pop() || 'file',
      uploadedBy: 'Client Admin'
    };

    if (db && user) {
      setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'brand2social', mockFileRecord.id), mockFileRecord);
    } else {
      setBrand2socialAttachments(prev => [mockFileRecord, ...prev]);
    }

    triggerToast(`Report "${file.name}" synchronized from Brand2social.com platform.`);
  };

  const handlePlanUpgrade = (pkgId) => {
    setActivePlanId(pkgId);
    const chosenPkg = packages.find(p => p.id === pkgId);
    setSocialProgress(prev => ({
      ...prev,
      postsCompleted: Math.min(prev.postsCompleted, chosenPkg.limits.posts),
      storiesCompleted: Math.min(prev.storiesCompleted, chosenPkg.limits.stories),
      reelsCompleted: Math.min(prev.reelsCompleted, chosenPkg.limits.reels)
    }));
    triggerToast(`Retainer bounds updated to match ${chosenPkg.name}.`);
    setIsUpgradeModalOpen(false);
  };

  const handleAdminAssignQuote = async (e) => {
    e.preventDefault();
    if (!selectedTaskForQuote) return;

    const taskToUpdate = customTasks.find(c => c.id === selectedTaskForQuote);
    if (!taskToUpdate) return;

    const updatedTask = {
      ...taskToUpdate,
      price: Number(assignQuotePrice),
      assignedTo: assignQuoteStaff,
      status: 'Awaiting Client Approval'
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'customTasks', selectedTaskForQuote), updatedTask);
    } else {
      setCustomTasks(prev => prev.map(c => c.id === selectedTaskForQuote ? updatedTask : c));
    }

    setSelectedTaskForQuote(null);
    setAssignQuotePrice(0);
    triggerToast(`Quote and pricing for "${taskToUpdate.title}" published for customer verification.`);
  };

  const handleSaveEditedPackage = async (e) => {
    e.preventDefault();
    if (!editingPackage) return;

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'packages', editingPackage.id), editingPackage);
    } else {
      setPackages(prev => prev.map(p => p.id === editingPackage.id ? editingPackage : p));
    }

    setIsEditPackageModalOpen(false);
    triggerToast(`Plan "${editingPackage.name}" configurations adjusted successfully.`);
  };

  const handleUpdateSocialMetrics = (metricKey, value) => {
    setSocialProgress(prev => {
      const updated = { ...prev, [metricKey]: Number(value) };
      if (metricKey === 'postsCompleted') updated.postsCompleted = Math.min(Number(value), activePlan.limits.posts);
      if (metricKey === 'storiesCompleted') updated.storiesCompleted = Math.min(Number(value), activePlan.limits.stories);
      if (metricKey === 'reelsCompleted') updated.reelsCompleted = Math.min(Number(value), activePlan.limits.reels);
      return updated;
    });
  };

  const handleAdminRequestApproval = async (requestId, newStatus) => {
    if (db && user) {
      await updateDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'requests', requestId), { status: newStatus });
    } else {
      setClientRequests(prev => prev.map(r => r.id === requestId ? { ...r, status: newStatus } : r));
    }
    triggerToast(`Request status updated to ${newStatus}.`);
  };

  const handleSaveAdminNotes = async (requestId) => {
    if (db && user) {
      await updateDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'requests', requestId), { adminNotes: tempNoteText });
    } else {
      setClientRequests(prev => prev.map(r => r.id === requestId ? { ...r, adminNotes: tempNoteText } : r));
    }
    setSelectedRequestForNote(null);
    setTempNoteText('');
    triggerToast("Commentary published successfully.");
  };

  const handleCreateDeliverable = async (e) => {
    e.preventDefault();
    if (!newDelivName.trim()) return;

    const newDeliv = {
      id: `deliv-${Date.now()}`,
      name: newDelivName,
      assignedTo: newDelivAssignee,
      status: newDelivStatus,
      type: newDelivType,
      dueDate: newDelivDueDate
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'deliverables', newDeliv.id), newDeliv);
    } else {
      setDeliverables(prev => [newDeliv, ...prev]);
    }

    setNewDelivName('');
    triggerToast(`Published PM deliverable "${newDeliv.name}" to client schedule board.`);
  };

  const handleDeleteDeliverable = async (delivId) => {
    if (db && user) {
      await deleteDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'deliverables', delivId));
    } else {
      setDeliverables(prev => prev.filter(d => d.id !== delivId));
    }
    triggerToast("Deliverable item removed from pipeline.");
  };

  const handleAdminGenerateInvoice = async (e) => {
    e.preventDefault();
    const invId = `inv-${Date.now()}`;
    const generatedInv = {
      id: invId,
      number: newInvNum || `INV-2026-00${invoices.length + 1}`,
      date: new Date().toISOString().split('T')[0],
      amount: Number(newInvAmount),
      status: newInvStatus,
      note: newInvNote || 'General SMMA Invoice Charge'
    };

    if (db && user) {
      await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'invoices', invId), generatedInv);
    } else {
      setInvoices(prev => [generatedInv, ...prev]);
    }

    if (newInvStatus === 'Paid') {
      const newLedgerLog = {
        date: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
        type: `Invoice Settled (${generatedInv.number})`,
        amount: generatedInv.amount,
        balance: ledger.length > 0 ? ledger[0].balance + generatedInv.amount : generatedInv.amount
      };
      setLedger(prev => [newLedgerLog, ...prev]);
    }

    setNewInvNum('');
    setNewInvNote('');
    triggerToast(`Retainer Invoice ${generatedInv.number} deployed for customer settlement.`);
  };

  const filteredRequests = useMemo(() => {
    return clientRequests.filter(req => 
      req.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      req.category.toLowerCase().includes(searchQuery.toLowerCase())
    );
  }, [clientRequests, searchQuery]);

  const filteredInvoices = useMemo(() => {
    let list = [...invoices];
    if (invoiceFilterStatus !== 'All') {
      list = list.filter(inv => inv.status === invoiceFilterStatus);
    }
    list.sort((a, b) => {
      const timeA = new Date(a.date).getTime();
      const timeB = new Date(b.date).getTime();
      return invoiceSortDir === 'asc' ? timeA - timeB : timeB - timeA;
    });
    return list;
  }, [invoices, invoiceFilterStatus, invoiceSortDir]);

  return (
    <div className="min-h-screen bg-[#f8fafc] flex font-sans text-slate-800 antialiased">
      
      {/* Toast Notification Container */}
      {toast && (
        <div className="fixed top-4 right-4 z-50 flex items-center gap-3 bg-slate-900 text-white px-5 py-4 rounded-xl shadow-2xl border border-slate-800 max-w-sm animate-in slide-in-from-top duration-300">
          <CheckCircle2 className="text-emerald-400 shrink-0 w-5 h-5" />
          <span className="text-xs font-medium">{toast.message}</span>
        </div>
      )}

      {}
      <aside className="w-64 bg-[#0f172a] text-slate-300 flex flex-col shrink-0 border-r border-slate-800 hidden md:flex justify-between">
        <div>
          {/* Brand Header */}
          <div className="p-6 border-b border-slate-800 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 bg-gradient-to-tr from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 font-bold text-xl">
                S
              </div>
              <div>
                <h1 className="text-base font-extrabold text-white tracking-wide leading-none">SMMA Scale</h1>
                <span className="text-[10px] text-indigo-400 font-bold tracking-wider uppercase mt-1 block">Unified Agency</span>
              </div>
            </div>
          </div>

          {/* Role status switcher indicator */}
          <div className="px-6 py-3 bg-[#1e293b]/60 border-b border-slate-800 flex flex-col gap-1">
            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Workspace View:</span>
            <div className="flex items-center gap-2">
              <span className={`px-2 py-0.5 rounded text-[10px] font-black uppercase ${
                currentRole === 'client' ? 'bg-indigo-50/20 text-indigo-400' :
                currentRole === 'pm' ? 'bg-sky-50/20 text-sky-450' :
                currentRole === 'finance' ? 'bg-emerald-50/20 text-emerald-400' :
                'bg-amber-50/20 text-amber-400'
              }`}>
                {currentRole === 'client' ? 'Client' :
                 currentRole === 'pm' ? 'PM Only (No Payments)' :
                 currentRole === 'finance' ? 'Finance Only' :
                 'Super Admin'}
              </span>
            </div>
          </div>

          {/* Nav list selection based on currentRole state */}
          <nav className="px-4 py-6 space-y-1.5 overflow-y-auto">
            
            {/* 1. Client Workspace Nav */}
            {currentRole === 'client' && (
              <>
                <p className="px-3 text-[10px] font-semibold tracking-wider text-slate-500 uppercase mb-2">Customer Panel</p>
                {[
                  { id: 'dashboard', label: 'Dashboard Overview', icon: LayoutDashboard },
                  { id: 'plan', label: 'Service Packages', icon: CreditCard },
                  { id: 'addons', label: 'Addons & Custom Projects', icon: Layers },
                  { id: 'deliverables', label: 'Deliverables Board', icon: CheckSquare },
                  { id: 'requests', label: 'Tasks & Support', icon: MessageSquare },
                  { id: 'payments', label: 'Billing Ledger', icon: FileText },
                  { id: 'reports', label: 'Marketing Reports', icon: BarChart3 },
                ].map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition-all duration-200 ${
                        isActive 
                          ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md shadow-indigo-600/20' 
                          : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                      }`}
                    >
                      <Icon className="w-4 h-4" />
                      <span className="truncate">{item.label}</span>
                    </button>
                  );
                })}
              </>
            )}

            {/* 2. Project Manager (PM) Workspace Nav - ABSOLUTELY NO PRICING OR PAYMENTS */}
            {currentRole === 'pm' && (
              <>
                <p className="px-3 text-[10px] font-semibold tracking-wider text-slate-500 uppercase mb-2">PM Operations Panel</p>
                {[
                  { id: 'pm-overview', label: 'Operations Desk', icon: LayoutDashboard },
                  { id: 'pm-deliverables', label: 'Manage Deliverables', icon: CheckSquare },
                  { id: 'pm-requests', label: 'Client Tickets & Tasks', icon: ShieldAlert },
                  { id: 'pm-verbal', label: 'Client Verbal Requests', icon: PlusCircle },
                  { id: 'pm-metrics', label: 'Progress Counter Sync', icon: Sliders },
                ].map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition-all duration-200 ${
                        isActive 
                          ? 'bg-gradient-to-r from-sky-600 to-sky-700 text-white shadow-md shadow-sky-600/20' 
                          : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                      }`}
                    >
                      <Icon className="w-4 h-4" />
                      <span className="truncate">{item.label}</span>
                    </button>
                  );
                })}
              </>
            )}

            {/* 3. Finance Manager Workspace Nav - ONLY FINANCES */}
            {currentRole === 'finance' && (
              <>
                <p className="px-3 text-[10px] font-semibold tracking-wider text-slate-500 uppercase mb-2">Finance Workspace</p>
                {[
                  { id: 'fin-overview', label: 'Ledger Summary', icon: Wallet },
                  { id: 'fin-invoices', label: 'Invoices & Billing', icon: FilePlus },
                  { id: 'fin-addons', label: 'PM Verbal Project Billing', icon: Coins },
                  { id: 'fin-plans', label: 'Subscription Packaging', icon: CreditCard },
                ].map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition-all duration-200 ${
                        isActive 
                          ? 'bg-gradient-to-r from-emerald-600 to-emerald-700 text-white shadow-md shadow-emerald-600/20' 
                          : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                      }`}
                    >
                      <Icon className="w-4 h-4" />
                      <span className="truncate">{item.label}</span>
                    </button>
                  );
                })}
              </>
            )}

            {/* 4. Super Admin Workspace Nav - CAN MANAGE BOTH PAYMENTS AND PROJECTS */}
            {currentRole === 'admin' && (
              <>
                <p className="px-3 text-[10px] font-semibold tracking-wider text-slate-500 uppercase mb-2">Super Admin Control</p>
                {[
                  { id: 'admin-overview', label: 'Command Overview', icon: LayoutDashboard },
                  { id: 'admin-plans', label: 'Configure Retainers', icon: CreditCard },
                  { id: 'admin-addons', label: 'All Project Scopes', icon: Layers },
                  { id: 'admin-deliverables', label: 'Global Deliverables', icon: CheckSquare },
                  { id: 'admin-requests', label: 'Resolve Tickets', icon: MessageSquare },
                  { id: 'admin-metrics', label: 'Sync Metrics Live', icon: Sliders },
                  { id: 'admin-invoices', label: 'Billing Ledgers Hub', icon: FilePlus },
                ].map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition-all duration-200 ${
                        isActive 
                          ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-md shadow-amber-600/20' 
                          : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'
                      }`}
                    >
                      <Icon className="w-4 h-4" />
                      <span className="truncate">{item.label}</span>
                    </button>
                  );
                })}
              </>
            )}

          </nav>
        </div>

        {}
        <div className="p-4 border-t border-slate-800 bg-[#0b0f19]">
          <div className="p-3 bg-slate-900 rounded-xl mb-4 border border-slate-800 space-y-2">
            <span className="text-[10px] font-extrabold uppercase text-slate-500 tracking-wider block">Workspace Mode:</span>
            
            <div className="grid grid-cols-2 gap-1.5 mb-1.5">
              <button
                onClick={() => handleRoleToggle('client')}
                className={`py-1.5 px-1 rounded text-[9px] font-bold transition truncate ${
                  currentRole === 'client' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-750'
                }`}
              >
                Client
              </button>
              <button
                onClick={() => handleRoleToggle('pm')}
                className={`py-1.5 px-1 rounded text-[9px] font-bold transition truncate ${
                  currentRole === 'pm' ? 'bg-sky-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-750'
                }`}
                title="Project Manager (No Financials)"
              >
                PM Desk
              </button>
            </div>

            <div className="grid grid-cols-2 gap-1.5">
              <button
                onClick={() => handleRoleToggle('finance')}
                className={`py-1.5 px-1 rounded text-[9px] font-bold transition truncate ${
                  currentRole === 'finance' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-750'
                }`}
                title="Finance Workspace"
              >
                Finance
              </button>
              <button
                onClick={() => handleRoleToggle('admin')}
                className={`py-1.5 px-1 rounded text-[9px] font-bold transition truncate ${
                  currentRole === 'admin' ? 'bg-amber-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-750'
                }`}
                title="Super Admin (All Controls)"
              >
                Admin (All)
              </button>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <img 
              className="h-10 w-10 rounded-lg object-cover ring-2 ring-indigo-500/20" 
              src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" 
              alt="Workspace Operator" 
            />
            <div className="overflow-hidden">
              <p className="text-xs font-bold text-white truncate">
                {currentRole === 'admin' ? 'Director Zack Admin' : 
                 currentRole === 'pm' ? 'PM Zack (Creative)' : 
                 currentRole === 'finance' ? 'Sarah Ledger (Finance)' : 
                 'Client Admin'}
              </p>
              <p className="text-[10px] text-slate-500 truncate">
                {currentRole === 'admin' ? 'Super Admin' : 
                 currentRole === 'pm' ? 'Creative Director' : 
                 currentRole === 'finance' ? 'Finance Executive' : 
                 'Client Portal'}
              </p>
            </div>
          </div>
        </div>
      </aside>

      {/* Main Container Area */}
      <main className="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        {/* Sticky Header Top Bar */}
        <header className="bg-white border-b border-slate-200 h-16 px-6 flex items-center justify-between sticky top-0 z-30 shrink-0 shadow-sm">
          <div className="relative w-80 max-w-xs">
            <span className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search className="h-4 w-4 text-slate-400" />
            </span>
            <input
              type="text"
              placeholder="Search tasks, deliverables, custom files..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="block w-full pl-9 pr-3 py-1.5 border border-slate-200 rounded-lg bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-xs"
            />
          </div>

          <div className="flex items-center gap-4">
            {currentRole === 'client' && (
              <button 
                onClick={() => setIsCustomTaskModalOpen(true)}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition"
              >
                <Plus className="w-3.5 h-3.5" />
                <span>Request Custom Task</span>
              </button>
            )}

            {currentRole === 'pm' && (
              <button 
                onClick={() => setIsVerbalRequestModalOpen(true)}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-50 text-sky-700 hover:bg-sky-100 rounded-lg text-xs font-semibold transition"
              >
                <Plus className="w-3.5 h-3.5" />
                <span>Add Verbal Project</span>
              </button>
            )}

            {currentRole === 'finance' && (
              <span className="px-2.5 py-1 bg-emerald-50 text-emerald-800 text-[10px] font-bold rounded-lg border border-emerald-200">
                Billing & Accounts Active
              </span>
            )}

            {currentRole === 'admin' && (
              <span className="px-2.5 py-1 bg-amber-50 text-amber-800 text-[10px] font-bold rounded-lg border border-amber-200">
                Super Admin Active
              </span>
            )}

            <button className="relative p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
              <Bell className="w-5 h-5" />
              <span className="absolute top-1 right-1 h-2 w-2 bg-rose-500 rounded-full ring-1 ring-white"></span>
            </button>
          </div>
        </header>

        {/* Primary Page Layout Box */}
        <div className="p-6 max-w-[1600px] w-full mx-auto space-y-6 flex-1">
          
          {/* Main Context Indicator Panel */}
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
            <div>
              <div className="flex items-center gap-2">
                <span className={`px-2.5 py-0.5 text-[10px] font-extrabold uppercase rounded-md ${
                  currentRole === 'client' ? 'bg-indigo-50 text-indigo-700' :
                  currentRole === 'pm' ? 'bg-sky-50 text-sky-700' :
                  currentRole === 'finance' ? 'bg-emerald-50 text-emerald-700' :
                  'bg-amber-50 text-amber-700'
                }`}>
                  {currentRole.toUpperCase()} WORKSPACE
                </span>
                <span className="text-xs text-slate-400 font-medium font-mono">Channel Secure Mode</span>
              </div>
              
              <h2 className="text-2xl font-black text-slate-900 tracking-tight mt-1">
                {currentRole === 'client' && "Your Client Workspace Portal"}
                {currentRole === 'pm' && "PM Operations Desk (No Payment Views)"}
                {currentRole === 'finance' && "Financial & Invoice Ledger Hub"}
                {currentRole === 'admin' && "Agency Command Center"}
              </h2>

              <p className="text-xs text-slate-500 mt-1">
                Currently tracking SMM Contract: <span className="font-bold text-indigo-600">{activePlan.name}</span>.
                {currentRole !== 'pm' && (
                  <> Monthly Base retainer: <span className="font-bold text-slate-950">{activePlan.price.toLocaleString()} PKR</span></>
                )}
              </p>
            </div>
            
            {currentRole === 'client' && (
              <div className="flex items-center gap-2 shrink-0">
                <button 
                  onClick={() => setIsUpgradeModalOpen(true)}
                  className="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold shadow-sm transition"
                >
                  <Sparkles className="w-3.5 h-3.5 text-yellow-400" />
                  <span>Change Plan Tier</span>
                </button>
              </div>
            )}
          </div>

          {}
          {currentRole === 'client' && (
            <>
              {/* Core metrics progress cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <div className="flex items-center justify-between text-slate-400 mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Posts Progress</span>
                    <CheckSquare className="w-4 h-4 text-indigo-500" />
                  </div>
                  <p className="text-2xl font-black text-slate-950">
                    {socialProgress.postsCompleted} <span className="text-xs font-medium text-slate-400">/ {activePlan.limits.posts} Posts</span>
                  </p>
                  <div className="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                    <div className="bg-indigo-600 h-1.5 rounded-full transition-all duration-500" style={{ width: `${(socialProgress.postsCompleted / activePlan.limits.posts) * 100}%` }} />
                  </div>
                </div>

                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <div className="flex items-center justify-between text-slate-400 mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Stories Completed</span>
                    <Sparkles className="w-4 h-4 text-pink-500" />
                  </div>
                  <p className="text-2xl font-black text-slate-950">
                    {socialProgress.storiesCompleted} <span className="text-xs font-medium text-slate-400">/ {activePlan.limits.stories} Stories</span>
                  </p>
                  <div className="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                    <div className="bg-pink-500 h-1.5 rounded-full transition-all duration-500" style={{ width: `${(socialProgress.storiesCompleted / activePlan.limits.stories) * 100}%` }} />
                  </div>
                </div>

                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <div className="flex items-center justify-between text-slate-400 mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Reels Released</span>
                    <TrendingUp className="w-4 h-4 text-emerald-500" />
                  </div>
                  <p className="text-2xl font-black text-slate-950">
                    {socialProgress.reelsCompleted} <span className="text-xs font-medium text-slate-400">/ {activePlan.limits.reels} Videos</span>
                  </p>
                  <div className="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                    <div className="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style={{ width: `${(socialProgress.reelsCompleted / activePlan.limits.reels) * 100}%` }} />
                  </div>
                </div>

                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <div className="flex items-center justify-between text-slate-400 mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider">Followers Gained</span>
                    <Users className="w-4 h-4 text-indigo-500" />
                  </div>
                  <p className="text-2xl font-black text-slate-950">+{socialProgress.followersGained.toLocaleString()}</p>
                  <p className="text-[10px] text-emerald-600 font-semibold mt-1">+18.4% growth since last update</p>
                </div>
              </div>

              {/* Client Dashboard Overview Tab */}
              {activeTab === 'dashboard' && (
                <div className="space-y-6">
                  <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div className="lg:col-span-8 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                      <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                        <div>
                          <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Social Growth Tracker</h3>
                          <p className="text-xs text-slate-500">Live statistics</p>
                        </div>
                        <span className="px-3 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg flex items-center gap-1.5">
                          <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                          Live Sync Channel Active
                        </span>
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                          <span className="text-[10px] font-bold text-slate-500 uppercase">Total Likes Logged</span>
                          <p className="text-2xl font-black text-indigo-900 mt-1">{socialProgress.totalLikes.toLocaleString()}</p>
                        </div>
                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                          <span className="text-[10px] font-bold text-slate-500 uppercase">Brand Mentions</span>
                          <p className="text-2xl font-black text-pink-900 mt-1">{socialProgress.brandMentions}</p>
                        </div>
                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                          <span className="text-[10px] font-bold text-slate-500 uppercase">Completed Social Ratio</span>
                          <p className="text-2xl font-black text-emerald-900 mt-1">
                            {Math.round(((socialProgress.postsCompleted + socialProgress.storiesCompleted + socialProgress.reelsCompleted) / (activePlan.limits.posts + activePlan.limits.stories + activePlan.limits.reels)) * 100)}%
                          </p>
                        </div>
                      </div>
                    </div>

                    {/* Brand2Social.com Hub Upload Area */}
                    <div className="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                      <div>
                        <div className="flex items-center gap-2 mb-2">
                          <Paperclip className="w-5 h-5 text-indigo-600" />
                          <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Brand2social Attachments</h3>
                        </div>
                        <p className="text-xs text-slate-500 mb-4">Review copies of your cross-channel analytics files.</p>
                        <div className="space-y-2.5 max-h-[160px] overflow-y-auto mb-4">
                          {brand2socialAttachments.map((att) => (
                            <div key={att.id} className="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-100 rounded-lg text-xs">
                              <div className="truncate pr-2">
                                <p className="font-semibold text-slate-900 truncate">{att.fileName}</p>
                                <span className="text-[9px] text-slate-400 block">{att.uploadDate} • {att.fileSize}</span>
                              </div>
                              <button onClick={() => triggerToast(`Downloading PDF: ${att.fileName}...`)} className="p-1.5 hover:bg-indigo-50 rounded-md text-indigo-600 shrink-0">
                                <Download className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          ))}
                        </div>
                      </div>
                      <div>
                        <input type="file" ref={fileInputRef} onChange={handleFileUpload} className="hidden" />
                        <button onClick={() => fileInputRef.current?.click()} className="w-full flex items-center justify-center gap-2 py-2.5 bg-indigo-50 border border-indigo-250 text-indigo-700 hover:bg-indigo-100 rounded-xl text-xs font-bold transition">
                          <Upload className="w-4 h-4" />
                          <span>Upload Brand2social File</span>
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Custom Out of Scope & Verbal Projects Desk */}
                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                      <div>
                        <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Additional Custom Projects & Verbal Add-ons</h3>
                        <p className="text-xs text-slate-500">Tasks requested through project chat or verbally added by the PM</p>
                      </div>
                      <button onClick={() => setActiveTab('addons')} className="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <span>Manage All Custom Addons</span>
                        <ChevronRight className="w-3.5 h-3.5" />
                      </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {customTasks.map((ct) => (
                        <div key={ct.id} className="p-4 border border-slate-150 rounded-xl bg-slate-50 text-xs flex flex-col justify-between">
                          <div className="space-y-2">
                            <div className="flex justify-between items-start">
                              <span className={`px-2 py-0.5 rounded text-[8px] font-extrabold uppercase ${ct.isVerbalRequest ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-indigo-50 text-indigo-700'}`}>
                                {ct.isVerbalRequest ? 'PM Verbal Add' : ct.category}
                              </span>
                              <span className="font-black text-slate-900">
                                {ct.price > 0 ? `${ct.price.toLocaleString()} PKR` : 'Awaiting Quote / Finance Valuation'}
                              </span>
                            </div>
                            <h4 className="font-extrabold text-slate-950">{ct.title}</h4>
                            <p className="text-slate-500 text-[11px] leading-relaxed">{ct.description}</p>
                          </div>

                          <div className="pt-3 border-t border-slate-100 mt-3 flex items-center justify-between">
                            <div className="flex flex-col">
                              <span className="text-[10px] text-slate-400">Status: <strong className="text-indigo-600">{ct.status}</strong></span>
                              <span className="text-[10px] text-slate-400">Lead Operator: {ct.assignedTo}</span>
                            </div>

                            {ct.status === 'Awaiting Client Approval' && (
                              <button onClick={() => handleClientApproveCustomProject(ct.id)} className="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold text-[10px]">
                                Approve & Pay
                              </button>
                            )}

                            {ct.status === 'Awaiting Client Payment' && (
                              <span className="text-amber-600 font-extrabold text-[10px] bg-amber-50 px-2 py-1 rounded">
                                Pending Custom Invoice Settlement
                              </span>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              )}

              {/* Client service packages config */}
              {activeTab === 'plan' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                  <h3 className="text-lg font-black text-slate-950">Service Plan Subscriptions</h3>
                  <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-2">
                    {packages.map((pkg) => {
                      const isActive = activePlanId === pkg.id;
                      return (
                        <div key={pkg.id} className={`p-5 rounded-2xl border flex flex-col justify-between space-y-4 ${isActive ? 'border-indigo-600 bg-indigo-50/5' : 'border-slate-200'}`}>
                          <div>
                            <h4 className="text-base font-extrabold text-slate-900">{pkg.name}</h4>
                            <p className="text-2xl font-black text-slate-950 mt-1">{pkg.price.toLocaleString()} PKR/Mo</p>
                            <ul className="space-y-1.5 mt-4 max-h-[160px] overflow-y-auto">
                              {pkg.features.map((feat, i) => (
                                <li key={i} className="text-xs text-slate-600 flex items-center gap-1.5">
                                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                                  <span>{feat}</span>
                                </li>
                              ))}
                            </ul>
                          </div>
                          <button onClick={() => handlePlanUpgrade(pkg.id)} disabled={isActive} className={`w-full py-2 rounded-lg text-xs font-bold ${isActive ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-900 text-white'}`}>
                            {isActive ? 'Current Active Package' : 'Switch Package'}
                          </button>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Client Addons tab */}
              {activeTab === 'addons' && (
                <div className="space-y-6">
                  <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <div className="flex justify-between items-center">
                      <div>
                        <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Out-of-Scope Customized Tasks</h3>
                        <p className="text-xs text-slate-500">Request brand development, app setups, and video production.</p>
                      </div>
                      <button onClick={() => setIsCustomTaskModalOpen(true)} className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition">
                        + Request Custom Project Quote
                      </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      {[
                        { title: 'Branding Booster (10 custom posts)', cost: '15,000 PKR', desc: 'Custom brand style assets, vectors, and typography setups.' },
                        { title: 'Elite Video Production (3 4K reels)', cost: '30,000 PKR', desc: 'Premium motion-graphic reel outputs.' },
                        { title: 'Custom Shopify/Wordpress Store Setup', cost: '80,000 PKR', desc: 'Full-stack store redesign, bank gateway connections and product integrations.' }
                      ].map((item, idx) => (
                        <div key={idx} className="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3 flex flex-col justify-between">
                          <div>
                            <h4 className="font-extrabold text-xs text-slate-900">{item.title}</h4>
                            <p className="text-[11px] text-slate-500 leading-relaxed mt-1">{item.desc}</p>
                          </div>
                          <div className="flex justify-between items-center pt-3 border-t border-slate-150">
                            <span className="font-bold text-slate-800 text-xs">{item.cost}</span>
                            <button 
                              onClick={async () => {
                                const newAdd = {
                                  id: `add-${Date.now()}`,
                                  name: item.title,
                                  type: 'Branding/Dev Addon',
                                  price: parseInt(item.cost.replace(/[^0-9]/g, '')),
                                  status: 'Pending Approval',
                                  progress: 0,
                                  metrics: 'Awaiting launch call'
                                };
                                if (db && user) {
                                  await setDoc(doc(db, 'artifacts', appId, 'users', user.uid, 'addons', newAdd.id), newAdd);
                                } else {
                                  setAddons(prev => [...prev, newAdd]);
                                }
                                triggerToast(`Custom add-on registered: ${item.title}`);
                              }}
                              className="px-2.5 py-1 bg-white border border-indigo-200 text-indigo-700 font-bold rounded text-[10px]"
                            >
                              Add Project
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                      <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Custom Project Tracker</h3>
                      <div className="space-y-3">
                        {customTasks.map((ct) => (
                          <div key={ct.id} className="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
                            <div className="flex justify-between font-bold">
                              <span>{ct.title}</span>
                              <span>{ct.price > 0 ? `${ct.price.toLocaleString()} PKR` : 'Awaiting Quote'}</span>
                            </div>
                            <p className="text-slate-500 text-[11px]">{ct.description}</p>
                            <div className="flex justify-between items-center pt-2 border-t border-slate-200 text-[10px] text-slate-400">
                              <span>Assigned to: {ct.assignedTo}</span>
                              <span className="font-extrabold text-indigo-700 uppercase">{ct.status}</span>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>

                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                      <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Active Add-on Progress</h3>
                      <div className="space-y-3">
                        {addons.map((add) => (
                          <div key={add.id} className="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
                            <div className="flex justify-between font-bold">
                              <span>{add.name}</span>
                              <span>{add.price.toLocaleString()} PKR</span>
                            </div>
                            <div className="flex justify-between text-[10px] text-slate-400">
                              <span>Progress: {add.metrics}</span>
                              <span>{add.status}</span>
                            </div>
                            <div className="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                              <div className="bg-indigo-600 h-full" style={{ width: `${add.progress}%` }} />
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Client Deliverables board */}
              {activeTab === 'deliverables' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                  <h3 className="text-lg font-black text-slate-950">Active Deliverable Timeline</h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {['To Do', 'In Progress', 'Done'].map((col) => (
                      <div key={col} className="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <span className="text-xs font-black uppercase text-slate-500 block mb-3">{col}</span>
                        <div className="space-y-3">
                          {deliverables.filter(d => d.status === col).map((deliv) => (
                            <div key={deliv.id} className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm text-xs">
                              <span className="text-[9px] bg-slate-50 text-slate-500 px-1.5 py-0.5 rounded font-bold mb-1 block w-max">{deliv.type}</span>
                              <h4 className="font-extrabold text-slate-950">{deliv.name}</h4>
                              <p className="text-[10px] text-slate-400 mt-2">Due: {deliv.dueDate}</p>
                            </div>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Client support tickets */}
              {activeTab === 'requests' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                  <div className="flex justify-between items-center border-b pb-3">
                    <h3 className="text-lg font-black text-slate-950">General Support & Tasks Hub</h3>
                    <button onClick={() => setIsRequestModalOpen(true)} className="px-3.5 py-1.5 bg-indigo-600 text-white rounded-lg font-bold text-xs">
                      + Submit Ticket
                    </button>
                  </div>
                  <div className="space-y-3.5">
                    {filteredRequests.map((req) => (
                      <div key={req.id} className="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
                        <div className="flex justify-between items-center font-bold">
                          <span className="text-slate-900 text-sm">{req.title}</span>
                          <span className="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-[10px] uppercase">{req.status}</span>
                        </div>
                        <p className="text-slate-600">{req.description}</p>
                        {req.adminNotes && (
                          <div className="p-3 bg-indigo-50/50 border border-indigo-100 rounded text-indigo-900">
                            <strong>PM Reply:</strong> {req.adminNotes}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Client Invoice Ledgers */}
              {activeTab === 'payments' && (
                <div className="space-y-6">
                  <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Invoices Ledger</h3>
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-xs font-semibold">
                        <thead>
                          <tr className="border-b border-slate-150 bg-slate-50">
                            <th className="py-2.5 px-4">Invoice Number</th>
                            <th className="py-2.5 px-4">Amount Charged</th>
                            <th className="py-2.5 px-4">Invoice Note</th>
                            <th className="py-2.5 px-4">Status</th>
                            <th className="py-2.5 px-4 text-right">Action</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {filteredInvoices.map((inv) => (
                            <tr key={inv.id} className="hover:bg-slate-50/50">
                              <td className="py-3 px-4 font-bold">{inv.number}</td>
                              <td className="py-3 px-4 font-bold">{inv.amount.toLocaleString()} PKR</td>
                              <td className="py-3 px-4 text-slate-500 italic max-w-xs truncate">{inv.note || 'General Retainer Fee'}</td>
                              <td className="py-3 px-4">
                                <span className={`px-2 py-0.5 rounded text-[9px] font-black uppercase ${inv.status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                                  {inv.status}
                                </span>
                              </td>
                              <td className="py-3 px-4 text-right">
                                {inv.status === 'Pending' ? (
                                  <button onClick={() => { setActiveInvoiceToPay(inv); setIsInvoiceModalOpen(true); }} className="px-3 py-1 bg-indigo-600 text-white rounded text-[10px] font-bold">
                                    Settle Invoice
                                  </button>
                                ) : (
                                  <span className="text-emerald-700 font-bold">Cleared ✓</span>
                                )}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

              {/* Reports view */}
              {activeTab === 'reports' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                  <div className="flex justify-between items-center">
                    <h3 className="text-lg font-black text-slate-900">Campaign Reports</h3>
                    <select value={platformSelected} onChange={(e) => setPlatformSelected(e.target.value)} className="p-1 border rounded text-xs">
                      {Object.keys(platformAdData).map(p => <option key={p} value={p}>{p}</option>)}
                    </select>
                  </div>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="p-3 bg-slate-50 border rounded-xl">
                      <span className="text-[10px] text-slate-400 block">Total Spend</span>
                      <strong className="text-slate-900 font-bold">{platformAdData[platformSelected].spend.toLocaleString()} PKR</strong>
                    </div>
                    <div className="p-3 bg-slate-50 border rounded-xl">
                      <span className="text-[10px] text-slate-400 block">Impressions</span>
                      <strong className="text-slate-900 font-bold">{platformAdData[platformSelected].impressions.toLocaleString()}</strong>
                    </div>
                    <div className="p-3 bg-slate-50 border rounded-xl">
                      <span className="text-[10px] text-slate-400 block">Conversions</span>
                      <strong className="text-indigo-600 font-bold">{platformAdData[platformSelected].conversions.toLocaleString()}</strong>
                    </div>
                  </div>
                </div>
              )}
            </>
          )}

          {}
          {currentRole === 'pm' && (
            <div className="space-y-6">
              
              {/* Quick warning banner showing PM cannot view payments */}
              <div className="p-4 bg-sky-50 border border-sky-100 rounded-2xl flex items-center justify-between text-sky-950">
                <div className="flex items-center gap-3">
                  <UserCheck className="w-5 h-5 text-sky-600 shrink-0" />
                  <div className="text-xs">
                    <p className="font-extrabold">Project Manager Creative Desk</p>
                    <p className="text-sky-750 font-semibold mt-0.5">All finance elements, PKR invoicing, and billing registry records are secured and hidden from this interface.</p>
                  </div>
                </div>
                
                <button 
                  onClick={() => setIsVerbalRequestModalOpen(true)}
                  className="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                >
                  + Add Verbal Project
                </button>
              </div>

              {/* PM Overview Dashboard */}
              {activeTab === 'pm-overview' && (
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                  
                  {/* Task metrics & verbal pipeline list */}
                  <div className="lg:col-span-8 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">
                      Ongoing Client Add-on & Custom Tasks
                    </h3>

                    <div className="space-y-3">
                      {customTasks.map((ct) => (
                        <div key={ct.id} className="p-4 border rounded-xl bg-slate-50/60 text-xs flex flex-col justify-between space-y-2">
                          <div className="flex justify-between items-start">
                            <span className="px-2 py-0.5 bg-sky-50 text-sky-700 font-black rounded uppercase text-[8px]">
                              {ct.isVerbalRequest ? 'PM Verbal client Task' : ct.category}
                            </span>
                            <span className="font-bold text-slate-500 font-mono text-[10px]">
                              Status: <strong className="text-sky-700">{ct.status}</strong>
                            </span>
                          </div>

                          <h4 className="font-extrabold text-slate-950 text-sm">{ct.title}</h4>
                          <p className="text-slate-600 leading-relaxed text-[11px] bg-white p-2.5 rounded border border-slate-100">
                            {ct.description}
                          </p>

                          <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <div className="flex items-center gap-3">
                              <span className="text-[10px] text-slate-400">Task Completion:</span>
                              <input 
                                type="range" 
                                min="0" 
                                max="100" 
                                value={ct.progress}
                                onChange={(e) => {
                                  const nextVal = Number(e.target.value);
                                  setCustomTasks(prev => prev.map(item => item.id === ct.id ? { ...item, progress: nextVal, status: nextVal === 100 ? 'Completed' : item.status } : item));
                                }}
                                className="accent-sky-600 w-24"
                                disabled={ct.status === 'Awaiting Invoice'}
                              />
                              <span className="font-bold text-slate-900">{ct.progress}%</span>
                            </div>

                            <span className="text-[10px] text-slate-400 italic font-medium">
                              {ct.isVerbalRequest ? 'Invoiced via Finance Queue' : 'Self-serve request'}
                            </span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Quick-add verbal task sidebar */}
                  <div className="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between space-y-4 text-xs">
                    <div>
                      <div className="flex items-center gap-2 pb-2 border-b border-slate-100 mb-2">
                        <MessageSquare className="w-4 h-4 text-sky-600" />
                        <h4 className="font-extrabold text-slate-900 uppercase">Verbal Project Addition</h4>
                      </div>
                      
                      <p className="text-slate-500 leading-relaxed text-[11px] mb-4">
                        Did the client verbally request a task over Zoom or WhatsApp? Add it below. Finance will immediately see it in their workspace to attach custom prices and invoice the client.
                      </p>

                      <form onSubmit={handleCreateVerbalRequest} className="space-y-3 font-semibold text-xs">
                        <div>
                          <label className="block text-slate-700 mb-1">Verbal Task Category</label>
                          <select value={verbalCategory} onChange={(e) => setVerbalCategory(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50">
                            <option value="Software Development">Software & Web dev</option>
                            <option value="Merchandise Printing">Physical Merch Setup</option>
                            <option value="Booklet / Ebook Generation">Manuals / PDF Publishing</option>
                            <option value="Paid Ads Extension">Paid ads setups</option>
                          </select>
                        </div>

                        <div>
                          <label className="block text-slate-700 mb-1">Task Title Summary</label>
                          <input type="text" required placeholder="e.g. Design 10 extra custom posts" value={verbalTitle} onChange={(e) => setVerbalTitle(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50 text-[11px]" />
                        </div>

                        <div>
                          <label className="block text-slate-700 mb-1">Verbal Context Specifications</label>
                          <textarea rows="3" required placeholder="Client verbal instructions..." value={verbalDesc} onChange={(e) => setVerbalDesc(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50 text-[11px] resize-none" />
                        </div>

                        <button type="submit" className="w-full py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg font-bold">
                          Post to Finance Invoice Pipeline
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              )}

              {/* PM Tab: Deliverables */}
              {activeTab === 'pm-deliverables' && (
                <div className="space-y-6">
                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Add deliverable target to client board</h3>
                    <form onSubmit={handleCreateDeliverable} className="grid grid-cols-1 md:grid-cols-5 gap-4 items-end text-xs font-semibold">
                      <div>
                        <label className="block text-slate-700 mb-1">Deliverable Title</label>
                        <input type="text" required placeholder="e.g. Schedule UGC Blog Copy" value={newDelivName} onChange={(e) => setNewDelivName(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Category Type</label>
                        <select value={newDelivType} onChange={(e) => setNewDelivType(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50">
                          <option value="Design">Elegant Design</option>
                          <option value="SEO Blog">SEO UGC Blog</option>
                          <option value="Paid Ads">Targeted Campaigns</option>
                          <option value="Setup">Technical Setup</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Assignee Specialist</label>
                        <input type="text" required value={newDelivAssignee} onChange={(e) => setNewDelivAssignee(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Schedule Target Date</label>
                        <input type="date" value={newDelivDueDate} onChange={(e) => setNewDelivDueDate(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>
                      <button type="submit" className="w-full py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg font-bold">
                        Publish Deliverable
                      </button>
                    </form>
                  </div>

                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Active Timeline Targets Board</h3>
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-xs font-semibold">
                        <thead>
                          <tr className="border-b bg-slate-50 text-slate-500 uppercase">
                            <th className="py-2.5 px-4">Deliverable Title</th>
                            <th className="py-2.5 px-4">Category</th>
                            <th className="py-2.5 px-4">Assignee</th>
                            <th className="py-2.5 px-4">Status</th>
                            <th className="py-2.5 px-4 text-right">Action</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {deliverables.map((deliv) => (
                            <tr key={deliv.id} className="hover:bg-slate-50/50">
                              <td className="py-3 px-4 font-bold">{deliv.name}</td>
                              <td className="py-3 px-4">{deliv.type}</td>
                              <td className="py-3 px-4 text-slate-600">{deliv.assignedTo}</td>
                              <td className="py-3 px-4">
                                <select 
                                  value={deliv.status}
                                  onChange={(e) => {
                                    const nextStatus = e.target.value;
                                    setDeliverables(prev => prev.map(d => d.id === deliv.id ? { ...d, status: nextStatus } : d));
                                    triggerToast(`Deliverable status updated to ${nextStatus}.`);
                                  }}
                                  className="bg-slate-50 rounded p-1 text-[11px]"
                                >
                                  <option value="To Do">To Do</option>
                                  <option value="In Progress">In Progress</option>
                                  <option value="Done">Done</option>
                                </select>
                              </td>
                              <td className="py-3 px-4 text-right">
                                <button onClick={() => handleDeleteDeliverable(deliv.id)} className="p-1 text-rose-600 hover:bg-rose-50 rounded">
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

              {/* PM Tab: Client Tickets & Tasks */}
              {activeTab === 'pm-requests' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Client Action requests & Tickets</h3>
                    <p className="text-xs text-slate-500 mt-1">Review task scopes generated by the customer team. Provide response commentary or resolve tickets.</p>
                  </div>

                  <div className="space-y-4">
                    {clientRequests.map((req) => (
                      <div key={req.id} className="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                        <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-2 text-xs">
                          <div className="flex items-center gap-2.5">
                            <span className="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-750">
                              {req.status}
                            </span>
                            <span className="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-50 text-indigo-700">
                              {req.category}
                            </span>
                            <h4 className="font-extrabold text-slate-950">{req.title}</h4>
                          </div>
                          <span className="text-slate-400 font-mono">Submitted: {req.date}</span>
                        </div>

                        <div className="p-3 bg-white rounded-lg border border-slate-200 text-xs text-slate-750">
                          <p>{req.description}</p>
                        </div>

                        {req.adminNotes && (
                          <div className="p-3 bg-indigo-50/50 rounded border border-indigo-100 text-xs text-indigo-900">
                            <strong>PM Note:</strong> {req.adminNotes}
                          </div>
                        )}

                        <div className="flex flex-wrap gap-2 pt-2 border-t border-slate-150 items-center justify-between text-xs">
                          <div className="flex gap-2">
                            <button onClick={() => handleAdminRequestApproval(req.id, 'Resolved')} className="px-3 py-1 bg-emerald-600 text-white rounded text-[10px] font-bold">
                              Resolve Ticket
                            </button>
                            <button onClick={() => setSelectedRequestForNote(req.id)} className="px-3 py-1 bg-slate-250 text-slate-700 rounded text-[10px] font-bold">
                              Add Reply Note
                            </button>
                          </div>

                          {selectedRequestForNote === req.id && (
                            <div className="w-full mt-2 space-y-2">
                              <textarea 
                                placeholder="Enter reply details..." 
                                value={tempNoteText} 
                                onChange={(e) => setTempNoteText(e.target.value)}
                                className="w-full p-2 border border-slate-300 rounded text-xs"
                              />
                              <button onClick={() => handleSaveAdminNotes(req.id)} className="px-3 py-1 bg-sky-600 text-white rounded font-bold">
                                Save Comment
                              </button>
                            </div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* PM Tab: Client Verbal Requests Tracker (No Financials) */}
              {activeTab === 'pm-verbal' && (
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                  <div className="flex justify-between items-center pb-2 border-b">
                    <div>
                      <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Verbal Requests Log</h3>
                      <p className="text-xs text-slate-500">Monitor whether verbal tasks have had pricing or custom invoices issued by Finance.</p>
                    </div>
                  </div>

                  <div className="space-y-3 text-xs">
                    {customTasks.filter(c => c.isVerbalRequest).map((task) => (
                      <div key={task.id} className="p-3.5 border border-slate-150 rounded-xl bg-slate-50 space-y-2">
                        <div className="flex justify-between font-bold">
                          <span>{task.title}</span>
                          <span className={`px-2 py-0.5 rounded text-[9px] font-black uppercase ${task.status === 'Awaiting Invoice' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>
                            {task.status}
                          </span>
                        </div>
                        <p className="text-slate-500 text-[11px]">{task.description}</p>
                        <p className="text-[10px] text-slate-400 font-medium italic">
                          {task.status === 'Awaiting Invoice' 
                            ? '🚨 Forwarded to Finance Manager. Awaiting PKR custom invoice setup.'
                            : '✓ Invoice generated. Client can now approve and pay.'}
                        </p>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* PM Tab: Social metrics progress counters sync */}
              {activeTab === 'pm-metrics' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                  <div>
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Social Progress counters</h3>
                    <p className="text-xs text-slate-500">Update completion metrics instantly for client widgets.</p>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs font-semibold">
                    <div className="space-y-4">
                      <div className="space-y-2">
                        <div className="flex justify-between">
                          <span>Feed Posts Completed</span>
                          <span className="text-indigo-600 font-extrabold">{socialProgress.postsCompleted} / {activePlan.limits.posts}</span>
                        </div>
                        <input type="range" min="0" max={activePlan.limits.posts} value={socialProgress.postsCompleted} onChange={(e) => handleUpdateSocialMetrics('postsCompleted', e.target.value)} className="w-full accent-indigo-600" />
                      </div>

                      <div className="space-y-2">
                        <div className="flex justify-between">
                          <span>Stories Completed</span>
                          <span className="text-pink-600 font-extrabold">{socialProgress.storiesCompleted} / {activePlan.limits.stories}</span>
                        </div>
                        <input type="range" min="0" max={activePlan.limits.stories} value={socialProgress.storiesCompleted} onChange={(e) => handleUpdateSocialMetrics('storiesCompleted', e.target.value)} className="w-full accent-pink-600" />
                      </div>
                    </div>

                    <div className="space-y-4">
                      <div className="grid grid-cols-2 gap-4">
                        <div>
                          <label className="block text-slate-500 mb-1">Likes Total</label>
                          <input type="number" value={socialProgress.totalLikes} onChange={(e) => handleUpdateSocialMetrics('totalLikes', e.target.value)} className="w-full p-2 border rounded-lg" />
                        </div>
                        <div>
                          <label className="block text-slate-500 mb-1">Followers Gained</label>
                          <input type="number" value={socialProgress.followersGained} onChange={(e) => handleUpdateSocialMetrics('followersGained', e.target.value)} className="w-full p-2 border rounded-lg" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              )}

            </div>
          )}

          {}
          {currentRole === 'finance' && (
            <div className="space-y-6">
              
              {/* Finance Security Banner */}
              <div className="p-4 bg-emerald-50 border border-emerald-150 rounded-2xl flex items-center justify-between text-emerald-950">
                <div className="flex items-center gap-3">
                  <Wallet className="w-5 h-5 text-emerald-600 shrink-0" />
                  <div className="text-xs">
                    <p className="font-extrabold">Finance Desk (Billing & Accounts Control)</p>
                    <p className="text-emerald-700 font-semibold mt-0.5">Manage base plans, adjust retainer pricing, log banking ledger records, and **issue custom invoices for PM verbal requests**.</p>
                  </div>
                </div>
              </div>

              {/* Finance Overview */}
              {activeTab === 'fin-overview' && (
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                  <div className="lg:col-span-8 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4 text-xs font-semibold">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Historical Credit Ledgers</h3>
                    <div className="space-y-3">
                      {ledger.map((log, idx) => (
                        <div key={idx} className="flex justify-between items-center py-2 border-b border-slate-100 text-xs">
                          <div>
                            <p className="font-bold text-slate-900">{log.type}</p>
                            <span className="text-[9px] text-slate-400 font-mono">{log.date}</span>
                          </div>
                          <div className="text-right">
                            <p className="font-black text-emerald-700">+{log.amount.toLocaleString()} PKR</p>
                            <span className="text-[9px] text-slate-400 font-mono">Bal: {log.balance.toLocaleString()} PKR</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between text-xs space-y-4">
                    <div>
                      <h4 className="font-extrabold text-slate-400 uppercase text-[10px]">Accounts Total Deposits</h4>
                      <p className="text-3xl font-black text-slate-950 mt-1">110,000 PKR</p>
                    </div>
                    <div className="p-3 bg-emerald-50 text-emerald-900 font-bold text-center rounded-xl">
                      ★ Audited SMM Invoicing Mode
                    </div>
                  </div>
                </div>
              )}

              {/* Finance Tab: Invoices & Billing */}
              {activeTab === 'fin-invoices' && (
                <div className="space-y-6">
                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Issue Manual Retainer Invoice</h3>
                    
                    <form onSubmit={handleAdminGenerateInvoice} className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end text-xs font-semibold">
                      <div>
                        <label className="block text-slate-700 mb-1">Invoice Code/Number</label>
                        <input type="text" placeholder="e.g. INV-2026-007" value={newInvNum} onChange={(e) => setNewInvNum(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>
                      
                      <div>
                        <label className="block text-slate-700 mb-1">Billing Fee Rate (PKR)</label>
                        <input type="number" required value={newInvAmount} onChange={(e) => setNewInvAmount(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>

                      <div>
                        <label className="block text-slate-700 mb-1">Invoice Status</label>
                        <select value={newInvStatus} onChange={(e) => setNewInvStatus(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50">
                          <option value="Pending">Pending Clearance</option>
                          <option value="Paid">Mark Settled</option>
                        </select>
                      </div>

                      <div>
                        <label className="block text-slate-700 mb-1">Invoice Description Note</label>
                        <input type="text" required placeholder="e.g. Graphic design setup charges" value={newInvNote} onChange={(e) => setNewInvNote(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>

                      <button type="submit" className="w-full py-2 bg-emerald-600 text-white rounded-lg font-bold">
                        Deploy Invoice
                      </button>
                    </form>
                  </div>

                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Client Invoices</h3>
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-xs font-semibold">
                        <thead>
                          <tr className="border-b bg-slate-50 uppercase text-slate-500">
                            <th className="py-2.5 px-4">Invoice Number</th>
                            <th className="py-2.5 px-4">Issued Date</th>
                            <th className="py-2.5 px-4">Amount Rate</th>
                            <th className="py-2.5 px-4">Notes</th>
                            <th className="py-2.5 px-4">Status</th>
                            <th className="py-2.5 px-4 text-right">Actions</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {invoices.map((inv) => (
                            <tr key={inv.id} className="hover:bg-slate-50/50">
                              <td className="py-3 px-4 font-bold">{inv.number}</td>
                              <td className="py-3 px-4 text-slate-500 font-mono">{inv.date}</td>
                              <td className="py-3 px-4 font-bold">{inv.amount.toLocaleString()} PKR</td>
                              <td className="py-3 px-4 text-slate-500 italic max-w-xs truncate">{inv.note || 'Retainer billing charge'}</td>
                              <td className="py-3 px-4">
                                <span className={`px-2 py-0.5 rounded text-[10px] uppercase font-bold ${inv.status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                                  {inv.status}
                                </span>
                              </td>
                              <td className="py-3 px-4 text-right">
                                {inv.status === 'Pending' ? (
                                  <button 
                                    onClick={() => {
                                      setInvoices(prev => prev.map(i => i.id === inv.id ? { ...i, status: 'Paid' } : i));
                                      triggerToast("Marked settled successfully");
                                    }}
                                    className="px-2 py-1 bg-emerald-600 text-white font-bold rounded text-[10px]"
                                  >
                                    Mark Paid
                                  </button>
                                ) : (
                                  <span className="text-slate-400 font-bold">Paid ✓</span>
                                )}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

              {/* Finance Tab: PM Verbal Request Billing (THE CORE ADD-ON COMPONENT) */}
              {activeTab === 'fin-addons' && (
                <div className="space-y-6">
                  
                  {/* Quoting & Custom Invoicing Form for Verbal PM tasks */}
                  {selectedVerbalForInvoice && (
                    <div className="bg-emerald-50 border border-emerald-200 p-6 rounded-2xl shadow-sm space-y-4">
                      <div>
                        <h3 className="text-sm font-extrabold text-emerald-950 uppercase tracking-wider">Issue Custom Invoice for PM Verbal Task</h3>
                        <p className="text-xs text-emerald-700 mt-1">Formulate the appropriate fee structure (PKR) and deploy the invoice linked directly to the task.</p>
                      </div>

                      <form onSubmit={handleFinanceGenerateCustomInvoice} className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end text-xs font-semibold">
                        <div>
                          <label className="block text-slate-700 mb-1">Billing Amount (PKR)</label>
                          <input 
                            type="number" 
                            required 
                            value={financeInvoicePrice} 
                            onChange={(e) => setFinanceInvoicePrice(e.target.value)}
                            className="w-full px-3 py-2 border rounded-lg bg-white"
                          />
                        </div>

                        <div>
                          <label className="block text-slate-700 mb-1">Invoice Number/Code</label>
                          <input 
                            type="text" 
                            required 
                            placeholder="e.g. INV-VERB-001" 
                            value={financeInvoiceNum} 
                            onChange={(e) => setFinanceInvoiceNum(e.target.value)}
                            className="w-full px-3 py-2 border rounded-lg bg-white"
                          />
                        </div>

                        <div className="flex gap-2">
                          <button type="submit" className="flex-1 py-2 bg-emerald-600 text-white rounded-lg font-bold">
                            Generate & Send Invoice
                          </button>
                          <button type="button" onClick={() => setSelectedVerbalForInvoice(null)} className="py-2 px-3 bg-white border rounded-lg text-slate-700 font-bold">
                            Cancel
                          </button>
                        </div>
                      </form>
                    </div>
                  )}

                  {/* Verbal Requests List Awaiting Invoices */}
                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">
                      Client Verbal Requests Added by PM (Awaiting Billing)
                    </h3>

                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-xs font-semibold">
                        <thead>
                          <tr className="border-b bg-slate-50 text-slate-500 uppercase">
                            <th className="py-2.5 px-4">Task Summary Title</th>
                            <th className="py-2.5 px-4">Category</th>
                            <th className="py-2.5 px-4">Details</th>
                            <th className="py-2.5 px-4">Billing Status</th>
                            <th className="py-2.5 px-4 text-right">Invoice Actions</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {customTasks.filter(c => c.isVerbalRequest).map((task) => (
                            <tr key={task.id} className="hover:bg-slate-50/50">
                              <td className="py-3 px-4 font-bold">{task.title}</td>
                              <td className="py-3 px-4">
                                <span className="px-2 py-0.5 bg-sky-50 text-sky-700 rounded text-[9px] font-semibold">{task.category}</span>
                              </td>
                              <td className="py-3 px-4 text-slate-500 italic max-w-xs truncate">{task.description}</td>
                              <td className="py-3 px-4">
                                <span className={`px-2 py-0.5 rounded text-[9px] font-extrabold uppercase ${task.status === 'Awaiting Invoice' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>
                                  {task.status}
                                </span>
                              </td>
                              <td className="py-3 px-4 text-right">
                                {task.status === 'Awaiting Invoice' ? (
                                  <button 
                                    onClick={() => {
                                      setSelectedVerbalForInvoice(task.id);
                                      setFinanceInvoiceNum(`INV-VERB-${Date.now().toString().slice(-4)}`);
                                    }}
                                    className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-[10px]"
                                  >
                                    Create Custom Invoice
                                  </button>
                                ) : (
                                  <span className="text-slate-400 font-semibold text-[10px]">Invoiced ✓</span>
                                )}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

              {/* Finance Tab: Subscription package retainer rates */}
              {activeTab === 'fin-plans' && (
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                  <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Manage Base Plan retainer Fee values</h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {packages.map((pkg) => (
                      <div key={pkg.id} className="border p-4 rounded-xl space-y-3 text-xs">
                        <div className="flex justify-between items-start">
                          <h4 className="font-extrabold text-slate-900">{pkg.name}</h4>
                          <button 
                            onClick={() => {
                              setEditingPackage({ ...pkg, featuresString: pkg.features.join('\n') });
                              setIsEditPackageModalOpen(true);
                            }}
                            className="p-1.5 bg-slate-100 rounded hover:bg-emerald-100 hover:text-emerald-800 transition"
                          >
                            <Edit2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                        <p className="text-2xl font-black text-slate-950">{pkg.price.toLocaleString()} PKR/Mo</p>
                      </div>
                    ))}
                  </div>
                </div>
              )}

            </div>
          )}

          {}
          {currentRole === 'admin' && (
            <>
              {/* Command Dashboard overview metrics */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <span className="text-[10px] font-bold text-slate-400 uppercase block mb-1">Total Client Retainers</span>
                  <p className="text-2xl font-black text-slate-900">165,000 PKR</p>
                </div>
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <span className="text-[10px] font-bold text-slate-400 uppercase block mb-1">Projects Pending Quotes</span>
                  <p className="text-2xl font-black text-amber-600">
                    {customTasks.filter(c => c.status === 'Awaiting Quote' || c.status === 'Awaiting Invoice').length}
                  </p>
                </div>
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <span className="text-[10px] font-bold text-slate-400 uppercase block mb-1">Active Deliverables Assigned</span>
                  <p className="text-2xl font-black text-indigo-650">
                    {deliverables.filter(d => d.status === 'In Progress').length}
                  </p>
                </div>
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                  <span className="text-[10px] font-bold text-slate-400 uppercase block mb-1">Open Client Requests</span>
                  <p className="text-2xl font-black text-rose-600">
                    {clientRequests.filter(r => r.status === 'Open').length}
                  </p>
                </div>
              </div>

              {/* Admin overview tab */}
              {activeTab === 'admin-overview' && (
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                  <div className="lg:col-span-8 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Super Admin Command Center</h3>
                    <p className="text-xs text-slate-500 leading-relaxed">
                      As the agency owner, you can manage both creative deliverables & financial accounts simultaneously. View package structures, change prices, update deliverable statuses, and sync completion metrics.
                    </p>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div className="border border-slate-150 p-4 rounded-xl bg-slate-50 space-y-2 text-xs">
                        <h4 className="font-extrabold text-slate-900 flex items-center gap-2">
                          <Layers className="w-4 h-4 text-amber-600" />
                          <span>Assign Custom Project Quotes</span>
                        </h4>
                        <p className="text-slate-500 text-[11px]">Set PKR rates for merch designs, website redesigns, and booklet projects.</p>
                        <button onClick={() => setActiveTab('admin-addons')} className="px-3 py-1.5 bg-amber-650 hover:bg-amber-700 text-white rounded font-bold transition">
                          Go to Creative Quoting Board
                        </button>
                      </div>

                      <div className="border border-slate-150 p-4 rounded-xl bg-slate-50 space-y-2 text-xs">
                        <h4 className="font-extrabold text-slate-900 flex items-center gap-2">
                          <Sliders className="w-4 h-4 text-indigo-600" />
                          <span>Progress Counter Live Sync</span>
                        </h4>
                        <p className="text-slate-500 text-[11px]">Control exact sliders and social growth metrics displayed to the client.</p>
                        <button onClick={() => setActiveTab('admin-metrics')} className="px-3 py-1.5 bg-indigo-650 hover:bg-indigo-700 text-white rounded font-bold transition">
                          Sync Progress Widgets
                        </button>
                      </div>
                    </div>
                  </div>

                  <div className="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between text-xs">
                    <div>
                      <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider mb-2">Pending Client Quotes</h3>
                      <div className="space-y-2.5 pt-2">
                        {customTasks.filter(c => c.status === 'Awaiting Quote').map((ct) => (
                          <div key={ct.id} className="p-3 bg-amber-50/60 border border-amber-200 rounded-lg">
                            <h4 className="font-extrabold text-slate-900">{ct.title}</h4>
                            <span className="text-[10px] text-slate-500 block mt-1">{ct.category}</span>
                            <button 
                              onClick={() => {
                                setSelectedTaskForQuote(ct.id);
                                setAssignQuotePrice(25000);
                              }}
                              className="mt-2 text-[10px] font-bold text-amber-800 hover:underline block"
                            >
                              Assign Price Quote & Link →
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Configure Packages Tab */}
              {activeTab === 'admin-plans' && (
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                  <div>
                    <h3 className="text-base font-extrabold text-slate-900">Configure retainer subscription models</h3>
                    <p className="text-xs text-slate-500">Edit base prices, feature matrices, and social post limits.</p>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {packages.map((pkg) => (
                      <div key={pkg.id} className="border border-slate-200 p-5 rounded-2xl space-y-4 flex flex-col justify-between">
                        <div className="space-y-3 text-xs">
                          <div className="flex justify-between items-start">
                            <h4 className="font-extrabold text-slate-900">{pkg.name}</h4>
                            <button 
                              onClick={() => {
                                setEditingPackage({ ...pkg, featuresString: pkg.features.join('\n') });
                                setIsEditPackageModalOpen(true);
                              }}
                              className="p-1.5 bg-slate-100 rounded hover:bg-amber-100 text-amber-800 transition"
                            >
                              <Edit2 className="w-3.5 h-3.5" />
                            </button>
                          </div>

                          <p className="text-2xl font-black text-slate-950">{pkg.price.toLocaleString()} PKR</p>
                          
                          <div className="p-3 bg-slate-50 rounded-lg text-[10px] space-y-1 font-semibold text-slate-600 border border-slate-100">
                            <p>📝 Posts Limit: {pkg.limits.posts}</p>
                            <p>⭐ Stories Limit: {pkg.limits.stories}</p>
                            <p>🎥 Reels Limit: {pkg.limits.reels}</p>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Admin Tab: Manage Custom & Addons */}
              {activeTab === 'admin-addons' && (
                <div className="space-y-6">
                  
                  {selectedTaskForQuote && (
                    <div className="bg-amber-50 border border-amber-200 p-6 rounded-2xl shadow-sm space-y-4">
                      <div>
                        <h3 className="text-sm font-extrabold text-amber-950 uppercase tracking-wider">Assign Custom Budget Rate</h3>
                        <p className="text-xs text-amber-700 mt-1">Submit dynamic pricing rate for the custom task requested by client.</p>
                      </div>

                      <form onSubmit={handleAdminAssignQuote} className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end text-xs font-semibold">
                        <div>
                          <label className="block text-slate-700 mb-1">Project Valuation (PKR)</label>
                          <input type="number" required value={assignQuotePrice} onChange={(e) => setAssignQuotePrice(e.target.value)} className="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" />
                        </div>

                        <div>
                          <label className="block text-slate-700 mb-1">Assign Creative Specialist</label>
                          <input type="text" required value={assignQuoteStaff} onChange={(e) => setAssignQuoteStaff(e.target.value)} className="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" />
                        </div>

                        <div className="flex gap-2">
                          <button type="submit" className="flex-1 py-2 bg-amber-600 text-white rounded-lg font-bold">
                            Send Quote
                          </button>
                          <button type="button" onClick={() => setSelectedTaskForQuote(null)} className="py-2 px-3 bg-white border border-slate-300 rounded-lg text-slate-700 font-bold">
                            Cancel
                          </button>
                        </div>
                      </form>
                    </div>
                  )}

                  <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">
                      Global Out-of-Scope Projects registry
                    </h3>

                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-xs font-semibold">
                        <thead>
                          <tr className="text-slate-500 font-bold uppercase border-b bg-slate-50">
                            <th className="py-2.5 px-4">Project Title</th>
                            <th className="py-2.5 px-4">Category</th>
                            <th className="py-2.5 px-4">PM Budget</th>
                            <th className="py-2.5 px-4">Current Status</th>
                            <th className="py-2.5 px-4">Execution Progress</th>
                            <th className="py-2.5 px-4 text-right">Actions</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {customTasks.map((ct) => (
                            <tr key={ct.id} className="hover:bg-slate-50/50">
                              <td className="py-3 px-4 font-bold max-w-xs truncate">{ct.title}</td>
                              <td className="py-3 px-4">
                                <span className="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[9px] font-semibold">{ct.category}</span>
                              </td>
                              <td className="py-3 px-4 font-bold">
                                {ct.price > 0 ? `${ct.price.toLocaleString()} PKR` : 'Awaiting Quote'}
                              </td>
                              <td className="py-3 px-4">
                                <span className={`px-2 py-0.5 rounded text-[9px] font-extrabold uppercase ${
                                  ct.status === 'In Progress' ? 'bg-amber-100 text-amber-800' :
                                  ct.status === 'Completed' ? 'bg-emerald-100 text-emerald-800' :
                                  'bg-slate-150 text-slate-650'
                                }`}>
                                  {ct.status}
                                </span>
                              </td>
                              <td className="py-3 px-4">
                                <div className="flex items-center gap-2">
                                  <input 
                                    type="range" min="0" max="100" value={ct.progress} 
                                    onChange={(e) => {
                                      const nextVal = Number(e.target.value);
                                      setCustomTasks(prev => prev.map(item => item.id === ct.id ? { ...item, progress: nextVal, status: nextVal === 100 ? 'Completed' : item.status } : item));
                                    }}
                                    className="accent-indigo-600 w-24"
                                    disabled={ct.status === 'Awaiting Quote' || ct.status === 'Awaiting Client Payment' || ct.status === 'Awaiting Invoice'}
                                  />
                                  <span>{ct.progress}%</span>
                                </div>
                              </td>
                              <td className="py-3 px-4 text-right">
                                {ct.status === 'Awaiting Quote' && (
                                  <button onClick={() => { setSelectedTaskForQuote(ct.id); setAssignQuotePrice(25000); }} className="px-2 py-1 bg-amber-600 text-white rounded font-bold text-[10px]">
                                    Assign Quote
                                  </button>
                                )}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

              {/* Admin Tab: Manage Deliverables */}
              {activeTab === 'admin-deliverables' && (
                <div className="space-y-6">
                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Add deliverable target</h3>
                    <form onSubmit={handleCreateDeliverable} className="grid grid-cols-1 md:grid-cols-5 gap-4 items-end text-xs font-semibold">
                      <div>
                        <label className="block text-slate-700 mb-1">Deliverable Title</label>
                        <input type="text" required placeholder="e.g. Layout Pinterest" value={newDelivName} onChange={(e) => setNewDelivName(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Type</label>
                        <select value={newDelivType} onChange={(e) => setNewDelivType(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50">
                          <option value="Design">Design Layout</option>
                          <option value="SEO Blog">SEO Copy</option>
                          <option value="Paid Ads">Targeted Ads</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Assignee Specialist</label>
                        <input type="text" required value={newDelivAssignee} onChange={(e) => setNewDelivAssignee(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Due Date</label>
                        <input type="date" value={newDelivDueDate} onChange={(e) => setNewDelivDueDate(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                      </div>
                      <button type="submit" className="w-full py-2 bg-amber-600 text-white rounded font-bold">
                        Add Deliverable
                      </button>
                    </form>
                  </div>

                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Active Client Deliverables timeline</h3>
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-xs font-semibold">
                        <thead>
                          <tr className="border-b bg-slate-50 text-slate-500 uppercase">
                            <th className="py-2.5 px-4">Title</th>
                            <th className="py-2.5 px-4">Category</th>
                            <th className="py-2.5 px-4">Assignee</th>
                            <th className="py-2.5 px-4">Status</th>
                            <th className="py-2.5 px-4 text-right">Action</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {deliverables.map((deliv) => (
                            <tr key={deliv.id} className="hover:bg-slate-50/50">
                              <td className="py-3 px-4 font-bold">{deliv.name}</td>
                              <td className="py-3 px-4">{deliv.type}</td>
                              <td className="py-3 px-4 text-slate-600">{deliv.assignedTo}</td>
                              <td className="py-3 px-4">
                                <select 
                                  value={deliv.status}
                                  onChange={(e) => {
                                    setDeliverables(prev => prev.map(d => d.id === deliv.id ? { ...d, status: e.target.value } : d));
                                    triggerToast(`Status changed`);
                                  }}
                                  className="bg-slate-50 p-1 rounded text-xs"
                                >
                                  <option value="To Do">To Do</option>
                                  <option value="In Progress">In Progress</option>
                                  <option value="Done">Done</option>
                                </select>
                              </td>
                              <td className="py-3 px-4 text-right">
                                <button onClick={() => handleDeleteDeliverable(deliv.id)} className="p-1 text-rose-600 hover:bg-rose-50 rounded">
                                  <Trash2 className="w-3.5 h-3.5" />
                                </button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}

              {/* Admin Tab: Client requests & tickets */}
              {activeTab === 'admin-requests' && (
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                  <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Manage Support requests</h3>
                  <div className="space-y-4">
                    {clientRequests.map((req) => (
                      <div key={req.id} className="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-2">
                        <div className="flex justify-between font-bold">
                          <span>{req.title}</span>
                          <span className="bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded text-[10px] uppercase">{req.status}</span>
                        </div>
                        <p className="text-slate-600">{req.description}</p>
                        <div className="flex gap-2">
                          <button onClick={() => handleAdminRequestApproval(req.id, 'Resolved')} className="px-3 py-1 bg-emerald-600 text-white rounded text-[10px] font-bold">
                            Resolve Ticket
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Admin Tab: Live progress counters */}
              {activeTab === 'admin-metrics' && (
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                  <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Adjust client overview social counters</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs font-semibold">
                    <div className="space-y-4">
                      <div className="space-y-2">
                        <label className="block text-slate-700">Posts Completed</label>
                        <input type="range" min="0" max={activePlan.limits.posts} value={socialProgress.postsCompleted} onChange={(e) => handleUpdateSocialMetrics('postsCompleted', e.target.value)} className="w-full accent-indigo-600" />
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Admin Tab: Invoices & Ledger Hub */}
              {activeTab === 'admin-invoices' && (
                <div className="space-y-6">
                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider pb-2 border-b">Deploy manual PKR charge</h3>
                    <form onSubmit={handleAdminGenerateInvoice} className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end text-xs font-semibold">
                      <div>
                        <label className="block text-slate-700 mb-1">Invoice Code</label>
                        <input type="text" placeholder="e.g. INV-2026-007" value={newInvNum} onChange={(e) => setNewInvNum(e.target.value)} className="w-full p-2 border rounded-lg bg-slate-50" />
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Billing fee (PKR)</label>
                        <input type="number" required value={newInvAmount} onChange={(e) => setNewInvAmount(e.target.value)} className="w-full p-2 border rounded-lg bg-slate-50" />
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Invoice Status</label>
                        <select value={newInvStatus} onChange={(e) => setNewInvStatus(e.target.value)} className="w-full p-2 border rounded-lg bg-slate-50">
                          <option value="Pending">Pending</option>
                          <option value="Paid">Mark Paid</option>
                        </select>
                      </div>
                      <div>
                        <label className="block text-slate-700 mb-1">Description Note</label>
                        <input type="text" required placeholder="Charge description..." value={newInvNote} onChange={(e) => setNewInvNote(e.target.value)} className="w-full p-2 border rounded-lg bg-slate-50" />
                      </div>
                      <button type="submit" className="w-full py-2 bg-amber-600 text-white rounded font-bold">
                        Deploy Invoice
                      </button>
                    </form>
                  </div>

                  <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                    <h3 className="text-sm font-extrabold text-slate-950 uppercase tracking-wider">Invoices registry</h3>
                    <div className="overflow-x-auto">
                      <table className="w-full text-left border-collapse text-xs font-semibold">
                        <thead>
                          <tr className="border-b bg-slate-50 uppercase text-slate-500">
                            <th className="py-2.5 px-4">Invoice Code</th>
                            <th className="py-2.5 px-4">Date</th>
                            <th className="py-2.5 px-4">Amount Rate</th>
                            <th className="py-2.5 px-4">Notes</th>
                            <th className="py-2.5 px-4">Status</th>
                            <th className="py-2.5 px-4 text-right">Controls</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {invoices.map((inv) => (
                            <tr key={inv.id} className="hover:bg-slate-50/50">
                              <td className="py-3 px-4 font-bold">{inv.number}</td>
                              <td className="py-3 px-4 text-slate-500 font-mono">{inv.date}</td>
                              <td className="py-3 px-4 font-bold">{inv.amount.toLocaleString()} PKR</td>
                              <td className="py-3 px-4 text-slate-500 italic max-w-xs truncate">{inv.note || 'Retainer fee'}</td>
                              <td className="py-3 px-4">
                                <span className={`px-2 py-0.5 rounded text-[10px] uppercase font-bold ${inv.status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                                  {inv.status}
                                </span>
                              </td>
                              <td className="py-3 px-4 text-right">
                                {inv.status === 'Pending' ? (
                                  <button onClick={() => { setInvoices(prev => prev.map(i => i.id === inv.id ? { ...i, status: 'Paid' } : i)); triggerToast(`Paid settled.`); }} className="px-2 py-1 bg-emerald-600 text-white rounded font-bold text-[10px]">
                                    Mark Settled
                                  </button>
                                ) : (
                                  <span className="text-emerald-700 font-bold">Cleared ✓</span>
                                )}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}
            </>
          )}

        </div>
      </main>

      {}
      {/* MODAL: Request Custom Task (Client Input) */}
      {isCustomTaskModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setIsCustomTaskModalOpen(false)}></div>
          <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150 space-y-4">
            <button onClick={() => setIsCustomTaskModalOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
              <X className="w-5 h-5" />
            </button>
            <div>
              <h3 className="text-base font-black text-slate-950">Request Custom Out-of-Scope Project</h3>
              <p className="text-xs text-slate-500">Add requests for booklet setups, website redesigns, physical merchandising layouts, or specialized software engineering.</p>
            </div>

            <form onSubmit={handleCreateCustomTaskRequest} className="space-y-4 text-xs font-semibold">
              <div>
                <label className="block text-slate-700 mb-1">Project Category</label>
                <select value={newCTCategory} onChange={(e) => setNewCTCategory(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-700 font-bold">
                  <option value="Software Development">Software & Web Development</option>
                  <option value="Merchandise Printing">Merchandise Layout Printing</option>
                  <option value="Booklet / Ebook Generation">Booklet or Ebook graphic setups</option>
                </select>
              </div>

              <div>
                <label className="block text-slate-700 mb-1">Project summary title</label>
                <input type="text" required placeholder="e.g. Layout for 100 ceramic mugs" value={newCTTitle} onChange={(e) => setNewCTTitle(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
              </div>

              <div>
                <label className="block text-slate-700 mb-1">Project context details</label>
                <textarea rows="3" placeholder="Enter specs..." value={newCTDesc} onChange={(e) => setNewCTDesc(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50 resize-none" />
              </div>

              <button type="submit" className="w-full py-2.5 bg-indigo-600 text-white font-extrabold rounded-xl transition">
                Request Quote
              </button>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Project Manager Quick Verbal Request Register Form */}
      {isVerbalRequestModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setIsVerbalRequestModalOpen(false)}></div>
          <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150 space-y-4">
            <button onClick={() => setIsVerbalRequestModalOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
              <X className="w-5 h-5" />
            </button>
            
            <div className="flex items-center gap-2 pb-2 border-b border-slate-100">
              <PlusCircle className="w-5 h-5 text-sky-600" />
              <div>
                <h3 className="text-base font-black text-slate-950">Add Verbal Client Project</h3>
                <p className="text-[11px] text-slate-400">Add the verbal task here. Finance will automatically see it and generate a custom invoice.</p>
              </div>
            </div>

            <form onSubmit={handleCreateVerbalRequest} className="space-y-4 text-xs font-semibold">
              <div>
                <label className="block text-slate-700 mb-1">Project Category</label>
                <select value={verbalCategory} onChange={(e) => setVerbalCategory(e.target.value)} className="w-full p-2.5 border border-slate-200 rounded-lg bg-slate-50">
                  <option value="Software Development">Software & Web Development</option>
                  <option value="Merchandise Printing">Merchandise Printing runs</option>
                  <option value="Booklet / Ebook Generation">Ebooks / Booklet Design</option>
                  <option value="Paid Ads Extension">Paid Campaign services</option>
                </select>
              </div>

              <div>
                <label className="block text-slate-700 mb-1">Task Summary Title</label>
                <input 
                  type="text" 
                  required 
                  placeholder="e.g. Graphic design for 10 extra posts" 
                  value={verbalTitle} 
                  onChange={(e) => setVerbalTitle(e.target.value)} 
                  className="w-full p-2.5 border border-slate-200 rounded-lg bg-slate-50" 
                />
              </div>

              <div>
                <label className="block text-slate-700 mb-1">Client Verbal Instructions</label>
                <textarea 
                  rows="3" 
                  required 
                  placeholder="What did the client request verbally? Enter details here..." 
                  value={verbalDesc} 
                  onChange={(e) => setVerbalDesc(e.target.value)} 
                  className="w-full p-2.5 border border-slate-200 rounded-lg bg-slate-50 resize-none" 
                />
              </div>

              <div className="p-3 bg-sky-50 text-sky-950 border border-sky-100 rounded-xl text-[11px] leading-relaxed">
                ℹ **Finance Notification Rule:** Saving this registers it as `Awaiting Invoice` on the Finance workspace.
              </div>

              <button type="submit" className="w-full py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-extrabold rounded-xl transition">
                Register Verbal Task
              </button>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Submit General Ticket (Client Form) */}
      {isRequestModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setIsRequestModalOpen(false)}></div>
          <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150 space-y-4">
            <button onClick={() => setIsRequestModalOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
              <X className="w-5 h-5" />
            </button>
            <div>
              <h3 className="text-base font-black text-slate-950">Submit General Support Ticket</h3>
            </div>

            <form onSubmit={handleCreateRequest} className="space-y-4 text-xs font-semibold">
              <div>
                <label className="block text-slate-700 mb-1">Category Type</label>
                <div className="grid grid-cols-2 gap-2">
                  <button 
                    type="button" onClick={() => setNewRequestCategory('Task Assignment')}
                    className={`py-2 px-3 rounded-lg border text-center font-bold ${
                      newRequestCategory === 'Task Assignment' ? 'border-indigo-600 bg-indigo-50/20 text-indigo-700' : 'border-slate-200 text-slate-600'
                    }`}
                  >
                    Task Assignment
                  </button>
                  <button 
                    type="button" onClick={() => setNewRequestCategory('Support Ticket')}
                    className={`py-2 px-3 rounded-lg border text-center font-bold ${
                      newRequestCategory === 'Support Ticket' ? 'border-rose-600 bg-rose-50/20 text-rose-700' : 'border-slate-200 text-slate-600'
                    }`}
                  >
                    Support Ticket
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-slate-700 mb-1">Ticket Subject</label>
                <input type="text" required placeholder="Instagram story modification..." value={newRequestTitle} onChange={(e) => setNewRequestTitle(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
              </div>

              <div>
                <label className="block text-slate-700 mb-1">Details</label>
                <textarea rows="3" placeholder="Explain details..." value={newRequestDesc} onChange={(e) => setNewRequestDesc(e.target.value)} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50 resize-none" />
              </div>

              <button type="submit" className="w-full py-2.5 bg-indigo-600 text-white font-extrabold rounded-xl transition">
                Send Request Task
              </button>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Secure Payment Checkout Sandbox */}
      {isInvoiceModalOpen && activeInvoiceToPay && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setIsInvoiceModalOpen(false)}></div>
          <div className="relative bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 animate-in fade-in duration-150 space-y-4 font-semibold">
            <button onClick={() => setIsInvoiceModalOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
              <X className="w-5 h-5" />
            </button>
            <div className="text-center pb-2 border-b border-slate-100">
              <h3 className="text-base font-black text-slate-900">Secure Payment Sandbox</h3>
              <p className="text-xs text-slate-500">Invoice: {activeInvoiceToPay.number}</p>
            </div>

            <div className="space-y-3.5 text-xs">
              <div className="flex justify-between text-slate-600">
                <span>Retainer Value</span>
                <span className="text-slate-950 font-black">{activeInvoiceToPay.amount.toLocaleString()} PKR</span>
              </div>
              <p className="text-[10px] text-slate-400 italic font-medium">This checkout sandbox simulates secure clearance paths.</p>
              
              <button onClick={() => handlePayInvoice(activeInvoiceToPay.id)} className="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-xl transition">
                Confirm PKR Settle
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Compare & Select Plan */}
      {isUpgradeModalOpen && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setIsUpgradeModalOpen(false)}></div>
          <div className="relative bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 animate-in duration-150 space-y-4">
            <button onClick={() => setIsUpgradeModalOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
              <X className="w-5 h-5" />
            </button>
            <div>
              <h3 className="text-lg font-black text-slate-950">SMM retainer Package</h3>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-semibold">
              {packages.map(pkg => (
                <div key={pkg.id} onClick={() => handlePlanUpgrade(pkg.id)} className={`p-4 rounded-xl border cursor-pointer transition flex flex-col justify-between hover:border-indigo-500 ${activePlanId === pkg.id ? 'border-indigo-600 bg-indigo-50/10' : 'border-slate-200 bg-slate-50/30'}`}>
                  <div>
                    <h4 className="font-extrabold text-xs text-slate-950 truncate">{pkg.name}</h4>
                    <p className="text-xs font-black text-slate-900 mt-1">{pkg.price.toLocaleString()} PKR/Month</p>
                  </div>
                  <span className="text-[10px] text-indigo-600 font-bold block mt-4">{activePlanId === pkg.id ? 'Selected' : 'Select Package'}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Admin Edit Package Detail */}
      {isEditPackageModalOpen && editingPackage && (
        <div className="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onClick={() => setIsEditPackageModalOpen(false)}></div>
          <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 animate-in duration-150 space-y-4">
            <button onClick={() => setIsEditPackageModalOpen(false)} className="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
              <X className="w-5 h-5" />
            </button>
            <div>
              <h3 className="text-base font-black text-slate-900">Configure retainer</h3>
            </div>

            <form onSubmit={handleSaveEditedPackage} className="space-y-4 text-xs font-semibold">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-slate-700 mb-1">Price Rate (PKR)</label>
                  <input type="number" required value={editingPackage.price} onChange={(e) => setEditingPackage({ ...editingPackage, price: Number(e.target.value) })} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                </div>
                <div>
                  <label className="block text-slate-700 mb-1">Posts Quota</label>
                  <input type="number" required value={editingPackage.limits.posts} onChange={(e) => setEditingPackage({ ...editingPackage, limits: { ...editingPackage.limits, posts: Number(e.target.value) } })} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50" />
                </div>
              </div>

              <div>
                <label className="block text-slate-700 mb-1">Features (one per line)</label>
                <textarea rows="5" value={editingPackage.featuresString} onChange={(e) => setEditingPackage({ ...editingPackage, featuresString: e.target.value, features: e.target.value.split('\n').filter(l => l.trim() !== '') })} className="w-full p-2 border border-slate-200 rounded-lg bg-slate-50 font-mono resize-none" />
              </div>

              <button type="submit" className="w-full py-2 bg-indigo-600 text-white rounded font-bold">
                Save Changes
              </button>
            </form>
          </div>
        </div>
      )}

    </div>
  );
}