<?php
// ==========================================
// PHP BACKEND (Mock Database & Data Injection)
// ==========================================

// Initial Packages (Packages ka data)
$packages = [
    [
        "id" => "pkg-1",
        "name" => "Package 1: Platinum Omnipresence",
        "price" => 65000,
        "limits" => ["posts" => 25, "stories" => 30, "reels" => 10],
        "features" => ["25 posts per month", "30 stories", "Monthly Report", "Targeted Ads"]
    ],
    [
        "id" => "pkg-2",
        "name" => "Package 2: Professional Growth",
        "price" => 55000,
        "limits" => ["posts" => 20, "stories" => 25, "reels" => 7],
        "features" => ["20 posts per month", "25 stories", "Monthly Report"]
    ]
];

// Initial Invoices (Invoices ka data)
$invoices = [
    ["id" => "inv-1", "number" => "INV-2026-006", "date" => "2026-06-15", "amount" => 55000, "status" => "Paid", "note" => "Standard Retainer"],
    ["id" => "inv-2", "number" => "INV-2026-003", "date" => "2026-03-15", "amount" => 45000, "status" => "Pending", "note" => "Starter Package"]
];

// Initial Custom Tasks (Custom Projects ka data)
$customTasks = [
    ["id" => "ct-1", "title" => "Corporate Ebook Design", "category" => "Design", "price" => 25000, "status" => "In Progress", "progress" => 65, "assignedTo" => "Sarah Design", "description" => "15-page interactive layout", "isVerbal" => false],
    ["id" => "ct-2", "title" => "Extra 10 Posts (Verbal)", "category" => "Design", "price" => 0, "status" => "Awaiting Invoice", "progress" => 0, "assignedTo" => "PM Verbal", "description" => "Client ne call par kaha tha", "isVerbal" => true]
];

// New: Deliverables Data
$deliverables = [
    ["id" => "deliv-1", "title" => "Draft 4x UGC Blogs", "assignedTo" => "Zack Media", "status" => "In Progress", "dueDate" => "2026-07-08"],
    ["id" => "deliv-2", "title" => "Design Content Calendar", "assignedTo" => "Sarah Design", "status" => "Done", "dueDate" => "2026-07-02"]
];

// New: Support Tickets Data
$tickets = [
    ["id" => "tkt-1", "title" => "Ad copy revision for Eid Campaign", "status" => "Open", "desc" => "Please tweak the Hook variations."],
    ["id" => "tkt-2", "title" => "Billing Issue: Charge on 15th", "status" => "Open", "desc" => "The payment system debited twice."]
];

// New: Metrics Data
$metrics = [
    "posts" => 14,
    "stories" => 19,
    "reels" => 5
];

// PHP array ko JSON mein convert kar k JavaScript ko dena
$initialStateJSON = json_encode([
    "packages" => $packages,
    "invoices" => $invoices,
    "customTasks" => $customTasks,
    "deliverables" => $deliverables,
    "tickets" => $tickets,
    "metrics" => $metrics
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin & Finance Portal</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased flex h-screen overflow-hidden">

    <!-- Sidebar (Left Menu) -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 border-r border-slate-800 h-full">
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <div class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg">S</div>
            <div>
                <h1 class="text-base font-extrabold text-white leading-none">SMMA Scale</h1>
                <span class="text-[10px] text-indigo-400 font-bold uppercase mt-1 block">Unified Agency</span>
            </div>
        </div>

        <!-- Role Switcher -->
        <div class="p-4 border-b border-slate-800 bg-slate-800/50 space-y-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Portal Mode:</span>
            <div class="flex gap-2">
                <button onclick="switchRole('admin')" id="btn-role-admin" class="flex-1 py-1.5 rounded text-[10px] font-bold bg-amber-600 text-white transition">Admin</button>
                <button onclick="switchRole('finance')" id="btn-role-finance" class="flex-1 py-1.5 rounded text-[10px] font-bold bg-slate-700 text-slate-400 hover:bg-slate-600 transition">Finance</button>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1.5" id="nav-menu">
            <!-- JS k zariye nav links render hongy -->
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0">
            <h2 class="text-lg font-black text-slate-800" id="header-title">Command Overview</h2>
            <div class="flex items-center gap-3">
                <span id="role-badge" class="px-2.5 py-1 text-[10px] font-bold rounded-lg border"></span>
            </div>
        </header>

        <!-- Dynamic Content -->
        <div class="flex-1 overflow-y-auto p-6" id="main-content">
            <!-- Yahan tab ka content render hoga -->
        </div>
    </main>

    <!-- JS Logic -->
    <script>
        // ==========================================
        // STATE MANAGEMENT (JavaScript)
        // ==========================================
        let state = {
            role: 'admin', // Default role
            activeTab: 'admin-overview',
            data: <?php echo $initialStateJSON; ?>
        };

        // DOM Elements
        const navMenu = document.getElementById('nav-menu');
        const mainContent = document.getElementById('main-content');
        const headerTitle = document.getElementById('header-title');
        const roleBadge = document.getElementById('role-badge');
        const btnRoleAdmin = document.getElementById('btn-role-admin');
        const btnRoleFinance = document.getElementById('btn-role-finance');

        // Menus configuration
        const menus = {
            admin: [
                { id: 'admin-overview', label: 'Command Overview', icon: 'layout-dashboard' },
                { id: 'admin-plans', label: 'Configure Retainers', icon: 'settings' },
                { id: 'admin-addons', label: 'All Project Scopes', icon: 'layers' },
                { id: 'admin-deliverables', label: 'Global Deliverables', icon: 'check-square' },
                { id: 'admin-tickets', label: 'Resolve Tickets', icon: 'message-square' },
                { id: 'admin-metrics', label: 'Sync Metrics Live', icon: 'sliders' },
                { id: 'admin-invoices', label: 'Billing Ledgers Hub', icon: 'file-text' }
            ],
            finance: [
                { id: 'fin-overview', label: 'Ledger Summary', icon: 'bar-chart-3' },
                { id: 'fin-invoices', label: 'Invoices & Billing', icon: 'file-plus' },
                { id: 'fin-addons', label: 'PM Verbal Project Billing', icon: 'coins' },
                { id: 'fin-plans', label: 'Subscription Packaging', icon: 'package' }
            ]
        };

        // ==========================================
        // CORE FUNCTIONS
        // ==========================================

        // Role tabdeel karne ka function
        function switchRole(role) {
            state.role = role;
            state.activeTab = menus[role][0].id; // Pehle tab par default
            
            // Buttons ki styling update karein
            if(role === 'admin') {
                btnRoleAdmin.className = "flex-1 py-1.5 rounded text-[10px] font-bold bg-amber-600 text-white transition";
                btnRoleFinance.className = "flex-1 py-1.5 rounded text-[10px] font-bold bg-slate-700 text-slate-400 hover:bg-slate-600 transition";
                roleBadge.className = "px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-800 border-amber-200";
                roleBadge.innerText = "Admin Workspace Active";
                headerTitle.innerText = "Command Overview";
            } else {
                btnRoleFinance.className = "flex-1 py-1.5 rounded text-[10px] font-bold bg-emerald-600 text-white transition";
                btnRoleAdmin.className = "flex-1 py-1.5 rounded text-[10px] font-bold bg-slate-700 text-slate-400 hover:bg-slate-600 transition";
                roleBadge.className = "px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-emerald-50 text-emerald-800 border-emerald-200";
                roleBadge.innerText = "Finance Workspace Active";
                headerTitle.innerText = "Ledger Summary";
            }

            renderSidebar();
            renderContent();
        }

        // Tab tabdeel karne ka function
        function switchTab(tabId) {
            state.activeTab = tabId;
            
            // Update header title based on active tab
            const currentMenu = menus[state.role].find(m => m.id === tabId);
            if(currentMenu) headerTitle.innerText = currentMenu.label;

            renderSidebar();
            renderContent();
        }

        // Sidebar render karne ka function
        function renderSidebar() {
            navMenu.innerHTML = '';
            const currentMenu = menus[state.role];
            
            currentMenu.forEach(item => {
                const isActive = state.activeTab === item.id;
                const activeClasses = isActive 
                    ? (state.role === 'admin' ? 'bg-amber-600 text-white shadow-md' : 'bg-emerald-600 text-white shadow-md') 
                    : 'text-slate-400 hover:bg-slate-800 hover:text-white';
                
                const btn = document.createElement('button');
                btn.className = `w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold transition-all ${activeClasses}`;
                btn.onclick = () => switchTab(item.id);
                btn.innerHTML = `<i data-lucide="${item.icon}" class="w-4 h-4"></i> <span class="truncate">${item.label}</span>`;
                navMenu.appendChild(btn);
            });
            lucide.createIcons();
        }

        // Action: Nayi invoice banana
        function createInvoice(e) {
            e.preventDefault();
            const num = document.getElementById('inv-num').value;
            const amount = document.getElementById('inv-amount').value;
            const status = document.getElementById('inv-status').value;
            const note = document.getElementById('inv-note').value;

            const newInv = {
                id: 'inv-' + Date.now(),
                number: num || `INV-2026-00${state.data.invoices.length + 1}`,
                date: new Date().toISOString().split('T')[0],
                amount: parseInt(amount),
                status: status,
                note: note
            };

            state.data.invoices.unshift(newInv);
            alert(`Success! Invoice ${newInv.number} deployed.`);
            renderContent();
        }

        // Action: Invoice status update karna
        function markInvoicePaid(id) {
            const inv = state.data.invoices.find(i => i.id === id);
            if(inv) {
                inv.status = 'Paid';
                alert('Invoice has been marked as Paid.');
                renderContent();
            }
        }

        // Action: Custom task par quote assign karna (Admin)
        function assignQuote(id) {
            const price = prompt("Enter the Quote Amount (PKR) for this project:");
            if(price && !isNaN(price)) {
                const task = state.data.customTasks.find(t => t.id === id);
                if(task) {
                    task.price = parseInt(price);
                    task.status = "Awaiting Client Approval";
                    alert(`Quote assigned: ${price} PKR`);
                    renderContent();
                }
            }
        }

        // Action: Verbal task par invoice generate karna (Finance)
        function generateVerbalInvoice(id) {
            const price = prompt("Enter the billing amount (PKR) for this verbal task:");
            if(price && !isNaN(price)) {
                const task = state.data.customTasks.find(t => t.id === id);
                if(task) {
                    task.price = parseInt(price);
                    task.status = "Invoiced";
                    
                    state.data.invoices.unshift({
                        id: 'inv-' + Date.now(),
                        number: 'INV-VERB-' + Date.now().toString().slice(-4),
                        date: new Date().toISOString().split('T')[0],
                        amount: parseInt(price),
                        status: 'Pending',
                        note: `Verbal Task Bill: ${task.title}`
                    });

                    alert("Custom invoice generated successfully!");
                    renderContent();
                }
            }
        }

        // Action: Resolve Ticket
        function resolveTicket(id) {
            const ticket = state.data.tickets.find(t => t.id === id);
            if(ticket) {
                ticket.status = "Resolved";
                alert("Ticket marked as resolved.");
                renderContent();
            }
        }

        // Action: Update Metrics Live
        function updateMetric(key, val) {
            state.data.metrics[key] = val;
            document.getElementById('val-' + key).innerText = val;
        }

        // ==========================================
        // CONTENT RENDERERS
        // ==========================================
        
        function renderContent() {
            let html = '';
            
            // -------- ADMIN VIEWS --------
            if(state.activeTab === 'admin-overview') {
                const pendingTasksCount = state.data.customTasks.filter(t => t.status === 'Invoice Ka Intezar' || t.status === 'Awaiting Quote' || t.status === 'Awaiting Invoice').length;
                html = `
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white p-5 rounded-2xl border shadow-sm">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Invoices</span>
                            <p class="text-2xl font-black text-slate-900">${state.data.invoices.length}</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border shadow-sm">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Pending Quotes</span>
                            <p class="text-2xl font-black text-amber-600">${pendingTasksCount}</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border shadow-sm">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Open Tickets</span>
                            <p class="text-2xl font-black text-rose-600">${state.data.tickets.filter(t=>t.status==='Open').length}</p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border shadow-sm">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Active Deliverables</span>
                            <p class="text-2xl font-black text-indigo-600">${state.data.deliverables.length}</p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">Command Overview</h3>
                        <p class="text-xs text-slate-500">Welcome to the Super Admin Dashboard. Use the sidebar to navigate through your agency's operations and financial controls.</p>
                    </div>
                `;
            }
            
            else if(state.activeTab === 'admin-plans') {
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">Configure Retainers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${state.data.packages.map(pkg => `
                                <div class="border border-slate-200 p-5 rounded-xl space-y-3 bg-slate-50">
                                    <h4 class="font-extrabold text-slate-900 text-sm">${pkg.name}</h4>
                                    <p class="text-2xl font-black text-amber-700">${numberFormat(pkg.price)} PKR</p>
                                    <div class="bg-white p-3 rounded border text-xs font-semibold space-y-1">
                                        <p>Posts Limit: ${pkg.limits.posts}</p>
                                        <p>Stories Limit: ${pkg.limits.stories}</p>
                                    </div>
                                    <button class="px-3 py-1.5 bg-slate-200 text-slate-700 rounded text-xs font-bold w-full hover:bg-amber-100 hover:text-amber-800">Edit Settings</button>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            else if(state.activeTab === 'admin-addons') {
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">All Project Scopes & Custom Quotes</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 border-b text-slate-500">
                                    <tr>
                                        <th class="py-2.5 px-3 uppercase">Project Title</th>
                                        <th class="py-2.5 px-3 uppercase">Budget (PKR)</th>
                                        <th class="py-2.5 px-3 uppercase">Current Status</th>
                                        <th class="py-2.5 px-3 text-right uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y font-semibold">
                                    ${state.data.customTasks.filter(t => !t.isVerbal).map(ct => `
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3 px-3 font-bold">${ct.title}</td>
                                            <td class="py-3 px-3">${ct.price > 0 ? numberFormat(ct.price) + ' PKR' : '<span class="text-slate-400 italic">Awaiting Quote</span>'}</td>
                                            <td class="py-3 px-3"><span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-extrabold uppercase">${ct.status}</span></td>
                                            <td class="py-3 px-3 text-right">
                                                ${ct.price === 0 ? `<button onclick="assignQuote('${ct.id}')" class="px-2.5 py-1.5 bg-amber-600 text-white rounded font-bold">Assign Quote</button>` : '<span class="text-slate-400 text-[10px]">Quoted ✓</span>'}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            else if(state.activeTab === 'admin-deliverables') {
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">Global Deliverables</h3>
                        <p class="text-xs text-slate-500">Track and manage timelines for all active client deliverables.</p>
                        <div class="overflow-x-auto mt-4">
                            <table class="w-full text-left text-xs font-semibold">
                                <thead class="bg-slate-50 border-b text-slate-500 uppercase">
                                    <tr>
                                        <th class="py-2.5 px-3">Title</th>
                                        <th class="py-2.5 px-3">Assignee</th>
                                        <th class="py-2.5 px-3">Due Date</th>
                                        <th class="py-2.5 px-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    ${state.data.deliverables.map(d => `
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3 px-3 font-bold text-slate-900">${d.title}</td>
                                            <td class="py-3 px-3 text-slate-600">${d.assignedTo}</td>
                                            <td class="py-3 px-3 text-slate-500 font-mono">${d.dueDate}</td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-extrabold ${d.status === 'Done' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800'}">${d.status}</span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            else if(state.activeTab === 'admin-tickets') {
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">Resolve Tickets</h3>
                        <div class="space-y-4">
                            ${state.data.tickets.map(t => `
                                <div class="p-4 border rounded-xl bg-slate-50">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-bold text-sm text-slate-900">${t.title}</h4>
                                        <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold ${t.status === 'Resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">${t.status}</span>
                                    </div>
                                    <p class="text-xs text-slate-600 mb-3">${t.desc}</p>
                                    ${t.status === 'Open' ? `<button onclick="resolveTicket('${t.id}')" class="px-3 py-1.5 bg-emerald-600 text-white rounded text-xs font-bold">Mark as Resolved</button>` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            else if(state.activeTab === 'admin-metrics') {
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">Sync Metrics Live</h3>
                        <p class="text-xs text-slate-500 mb-4">Adjust the progress counters shown on the client dashboard.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs font-semibold">
                            <div class="space-y-4 bg-slate-50 p-4 border rounded-xl">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <label class="text-slate-700 uppercase font-bold text-[10px]">Posts Completed</label>
                                        <span class="text-indigo-600 font-black" id="val-posts">${state.data.metrics.posts}</span>
                                    </div>
                                    <input type="range" min="0" max="30" value="${state.data.metrics.posts}" oninput="updateMetric('posts', this.value)" class="w-full accent-indigo-600">
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <label class="text-slate-700 uppercase font-bold text-[10px]">Stories Completed</label>
                                        <span class="text-pink-600 font-black" id="val-stories">${state.data.metrics.stories}</span>
                                    </div>
                                    <input type="range" min="0" max="30" value="${state.data.metrics.stories}" oninput="updateMetric('stories', this.value)" class="w-full accent-pink-600">
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <label class="text-slate-700 uppercase font-bold text-[10px]">Reels Completed</label>
                                        <span class="text-emerald-600 font-black" id="val-reels">${state.data.metrics.reels}</span>
                                    </div>
                                    <input type="range" min="0" max="15" value="${state.data.metrics.reels}" oninput="updateMetric('reels', this.value)" class="w-full accent-emerald-600">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            // -------- SHARED: INVOICES --------
            else if(state.activeTab === 'admin-invoices' || state.activeTab === 'fin-invoices') {
                const btnColor = state.role === 'admin' ? 'bg-amber-600' : 'bg-emerald-600';
                const hubTitle = state.role === 'admin' ? 'Billing Ledgers Hub' : 'Invoices & Billing';
                html = `
                    <div class="space-y-6">
                        <!-- Invoice Form -->
                        <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                            <h3 class="text-sm font-extrabold text-slate-900 uppercase">Create Manual Invoice</h3>
                            <form onsubmit="createInvoice(event)" class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs font-bold">
                                <div><label class="text-slate-600 block mb-1">Invoice Code</label><input id="inv-num" type="text" placeholder="e.g. INV-007" class="w-full p-2 border rounded-lg bg-slate-50"></div>
                                <div><label class="text-slate-600 block mb-1">Billing Amount (PKR)</label><input id="inv-amount" type="number" required class="w-full p-2 border rounded-lg bg-slate-50"></div>
                                <div>
                                    <label class="text-slate-600 block mb-1">Status</label>
                                    <select id="inv-status" class="w-full p-2 border rounded-lg bg-slate-50">
                                        <option value="Pending">Pending Clearance</option>
                                        <option value="Paid">Mark as Paid</option>
                                    </select>
                                </div>
                                <div><label class="text-slate-600 block mb-1">Description Note</label><input id="inv-note" type="text" placeholder="Fee details..." class="w-full p-2 border rounded-lg bg-slate-50"></div>
                                <button type="submit" class="w-full py-2.5 ${btnColor} text-white rounded-lg font-bold md:col-span-4 mt-2">Deploy Invoice</button>
                            </form>
                        </div>

                        <!-- Invoices List -->
                        <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                            <h3 class="text-sm font-extrabold text-slate-900 uppercase">${hubTitle}</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 border-b text-slate-500">
                                        <tr>
                                            <th class="py-2.5 px-3 uppercase">Invoice Code</th>
                                            <th class="py-2.5 px-3 uppercase">Date</th>
                                            <th class="py-2.5 px-3 uppercase">Amount</th>
                                            <th class="py-2.5 px-3 uppercase">Status</th>
                                            <th class="py-2.5 px-3 text-right uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y font-semibold">
                                        ${state.data.invoices.map(inv => `
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-3 px-3 font-bold">${inv.number}</td>
                                                <td class="py-3 px-3 text-slate-500 font-mono">${inv.date}</td>
                                                <td class="py-3 px-3 font-bold">${numberFormat(inv.amount)} PKR</td>
                                                <td class="py-3 px-3">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase ${inv.status === 'Paid' || inv.status === 'Ada Shuda' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">${inv.status === 'Ada Shuda' ? 'Paid' : inv.status}</span>
                                                </td>
                                                <td class="py-3 px-3 text-right">
                                                    ${inv.status === 'Pending' || inv.status === 'Baqaya' 
                                                        ? `<button onclick="markInvoicePaid('${inv.id}')" class="px-2.5 py-1 ${btnColor} text-white rounded font-bold text-[10px]">Mark Settled</button>` 
                                                        : '<span class="text-emerald-700 font-bold text-[10px]">Cleared ✓</span>'}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }

            // -------- FINANCE VIEWS --------
            else if(state.activeTab === 'fin-overview') {
                const totalPaid = state.data.invoices.filter(i => i.status === 'Paid' || i.status === 'Ada Shuda').reduce((sum, i) => sum + i.amount, 0);
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm text-center py-12">
                        <div class="h-16 w-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="wallet" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-sm font-extrabold text-slate-500 uppercase">Ledger Summary (Total Revenue)</h3>
                        <p class="text-4xl font-black text-slate-900 mt-2">${numberFormat(totalPaid)} PKR</p>
                        <p class="text-xs text-emerald-600 font-bold mt-2">★ Includes only settled & paid invoices</p>
                    </div>
                `;
            }

            else if(state.activeTab === 'fin-addons') {
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">PM Verbal Project Billing</h3>
                        <p class="text-xs text-slate-500">Generate custom invoices for out-of-scope tasks verbally requested by the client and logged by the Project Manager.</p>
                        <div class="overflow-x-auto mt-4">
                            <table class="w-full text-left text-xs font-semibold">
                                <thead class="bg-slate-50 border-b text-slate-500">
                                    <tr>
                                        <th class="py-2.5 px-3 uppercase">Task Summary</th>
                                        <th class="py-2.5 px-3 uppercase">Details</th>
                                        <th class="py-2.5 px-3 uppercase">Billing Status</th>
                                        <th class="py-2.5 px-3 text-right uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    ${state.data.customTasks.filter(t => t.isVerbal).map(ct => `
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-3 px-3 font-bold text-slate-900">${ct.title}</td>
                                            <td class="py-3 px-3 text-slate-500 italic">${ct.description}</td>
                                            <td class="py-3 px-3"><span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-extrabold uppercase">${ct.status === 'Invoice Ka Intezar' ? 'Awaiting Invoice' : ct.status}</span></td>
                                            <td class="py-3 px-3 text-right">
                                                ${ct.status === 'Awaiting Invoice' || ct.status === 'Invoice Ka Intezar' 
                                                    ? `<button onclick="generateVerbalInvoice('${ct.id}')" class="px-2.5 py-1 bg-emerald-600 text-white rounded font-bold text-[10px]">Create Custom Invoice</button>` 
                                                    : '<span class="text-emerald-700 font-bold text-[10px]">Invoiced ✓</span>'}
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            else if(state.activeTab === 'fin-plans') {
                html = `
                    <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-4">
                        <h3 class="text-sm font-extrabold text-slate-900 uppercase">Subscription Packaging (Retainer Rates)</h3>
                        <p class="text-xs text-slate-500 mb-4">View and manage the base pricing configurations for monthly agency retainers.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${state.data.packages.map(pkg => `
                                <div class="border border-slate-200 p-5 rounded-xl space-y-3 bg-emerald-50/30">
                                    <h4 class="font-extrabold text-slate-900 text-sm">${pkg.name}</h4>
                                    <p class="text-2xl font-black text-emerald-700">${numberFormat(pkg.price)} PKR / Month</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            mainContent.innerHTML = html;
            lucide.createIcons();
        }

        // Helper for formatting numbers
        function numberFormat(num) {
            return Number(num).toLocaleString();
        }

        // Initialize App
        window.onload = () => {
            switchRole('admin'); // Default view
        };
    </script>
</body>
</html>