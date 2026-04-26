<?php
session_start();
// Security: Redirect to login if not authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require 'db.php';
$is_admin = ($_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>V-ELITE v5.7 | Logger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=JetBrains+Mono:wght@700&display=swap');
        :root { --bg: #020617; --card: rgba(30, 41, 59, 0.7); --text: #f8fafc; --border: rgba(255, 255, 255, 0.1); }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text); min-height: 100dvh; text-transform: uppercase; margin: 0; padding: 0; display: flex; flex-direction: column; overflow: hidden; }
        .glass { background: var(--card); backdrop-filter: blur(10px); border: 1px solid var(--border); border-radius: 1.2rem; }
        .score-font { font-family: 'JetBrains Mono', monospace; line-height: 1; }
        .res-btn { display: flex; flex-direction: column; padding: 6px; border-radius: 10px; font-weight: 800; flex: 1; text-align: center; }
        .to-style { color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05); }
        .sub-style { color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05); }
        .team-a-theme { border-left: 6px solid #2563eb !important; }
        .team-b-theme { border-left: 6px solid #ea580c !important; }
        .serving { outline: 3px solid #facc15; box-shadow: 0 0 20px rgba(250, 204, 21, 0.3); }
        @media print { .no-print { display: none !important; } .print-only { display: block !important; } }
    </style>
</head>
<body class="p-2 gap-2">

    <div id="initModal" class="fixed inset-0 z-[100] bg-slate-950 flex items-center justify-center p-6 no-print">
        <div class="glass p-8 w-full max-w-xs text-center shadow-2xl">
            <h1 class="text-3xl font-black mb-6 italic text-white">V-ELITE <span class="text-blue-500">5.7</span></h1>
            <div class="space-y-4">
                <button onclick="startMatch(3)" class="w-full bg-blue-600 py-4 rounded-xl font-black text-white">BEST OF 3</button>
                <button onclick="startMatch(5)" class="w-full bg-slate-800 py-4 rounded-xl font-black text-white">BEST OF 5</button>
            </div>
            <p class="mt-6 text-[9px] font-bold text-slate-500">LOGGED IN AS: <?php echo strtoupper($_SESSION['username']); ?></p>
        </div>
    </div>

    <div id="nextSetModal" class="fixed inset-0 z-[110] bg-slate-950/95 backdrop-blur-xl flex items-center justify-center p-6 hidden no-print">
        <div class="glass p-8 w-full max-w-xs text-center border-blue-500 border-2">
            <h2 id="setWinnerMsg" class="text-xl font-black mb-6 text-white leading-tight"></h2>
            <button onclick="confirmNextSet()" class="w-full bg-blue-600 py-5 rounded-xl font-black text-white shadow-xl">START NEXT SET</button>
        </div>
    </div>

    <header class="flex justify-between items-center px-4 py-2 glass no-print shrink-0">
        <div class="leading-tight">
            <input type="text" id="matchID" value="OFFICIAL MATCH" class="bg-transparent font-black text-blue-500 text-[10px] w-28 outline-none uppercase">
            <div id="setInfo" class="text-[9px] font-bold opacity-50 uppercase">SET 1</div>
        </div>
        <div class="flex gap-2 items-center">
            <?php if($is_admin): ?>
                <a href="users.php" class="w-8 h-8 flex items-center justify-center glass text-[12px]">⚙️</a>
                <button onclick="resetMatch()" class="px-3 h-8 glass text-red-500 text-[9px] font-black">RESET</button>
            <?php endif; ?>
            <button onclick="undo()" class="px-3 h-8 glass text-[9px] font-bold">UNDO</button>
            <a href="logout.php" class="flex items-center justify-center px-3 h-8 glass text-slate-400 text-[9px] font-black uppercase">EXIT</a>
        </div>
    </header>

    <main class="flex-grow flex flex-col gap-2 no-print min-h-0">
        <div id="cardA" class="glass team-a-theme flex-1 flex flex-col justify-center px-4 py-2">
            <div class="flex justify-between items-center mb-1">
                <input type="text" id="nameA" value="HOME" class="bg-transparent font-black text-[10px] outline-none w-3/4 uppercase">
                <div id="srvA" class="w-3 h-3 rounded-full bg-yellow-400 hidden"></div>
            </div>
            <div class="flex justify-between items-center">
                <span id="scoreA" class="score-font text-7xl tracking-tighter">00</span>
                <div class="text-right">
                    <div id="setsA" class="text-4xl font-black text-blue-500">0</div>
                    <div id="remA" class="text-[7px] font-bold opacity-40 uppercase">TO WIN: 25</div>
                </div>
            </div>
            <div class="flex gap-2 mt-2">
                <button id="btnToA" onclick="recordAction('TO', 'A')" class="res-btn to-style"><span class="text-[6px]">TIMEOUT</span><span id="numToA" class="text-xs">2</span></button>
                <button id="btnSubA" onclick="recordAction('SUB', 'A')" class="res-btn sub-style"><span class="text-[6px]">SUB</span><span id="numSubA" class="text-xs">6</span></button>
            </div>
        </div>

        <div id="cardB" class="glass team-b-theme flex-1 flex flex-col justify-center px-4 py-2">
            <div class="flex justify-between items-center mb-1">
                <input type="text" id="nameB" value="GUEST" class="bg-transparent font-black text-[10px] outline-none w-3/4 uppercase">
                <div id="srvB" class="w-3 h-3 rounded-full bg-yellow-400 hidden"></div>
            </div>
            <div class="flex justify-between items-center">
                <span id="scoreB" class="score-font text-7xl tracking-tighter">00</span>
                <div class="text-right">
                    <div id="setsB" class="text-4xl font-black text-orange-500">0</div>
                    <div id="remB" class="text-[7px] font-bold opacity-40 uppercase">TO WIN: 25</div>
                </div>
            </div>
            <div class="flex gap-2 mt-2">
                <button id="btnToB" onclick="recordAction('TO', 'B')" class="res-btn to-style"><span class="text-[6px]">TIMEOUT</span><span id="numToB" class="text-xs">2</span></button>
                <button id="btnSubB" onclick="recordAction('SUB', 'B')" class="res-btn sub-style"><span class="text-[6px]">SUB</span><span id="numSubB" class="text-xs">6</span></button>
            </div>
        </div>
    </main>

    <footer class="grid grid-cols-2 gap-2 h-24 shrink-0 no-print">
        <button onclick="addPoint('A')" class="bg-blue-600 active:bg-blue-700 rounded-2xl font-black text-white text-xl shadow-lg transition-transform active:scale-95">POINT A</button>
        <button onclick="addPoint('B')" class="bg-orange-600 active:bg-orange-700 rounded-2xl font-black text-white text-xl shadow-lg transition-transform active:scale-95">POINT B</button>
    </footer>

    <div id="finishModal" class="fixed inset-0 z-[200] bg-slate-950 flex items-center justify-center p-6 hidden no-print">
        <div class="glass bg-white text-slate-950 p-8 w-full max-w-xs text-center">
            <h2 id="finalWinnerUI" class="text-2xl font-black mb-6 italic uppercase"></h2>
            <p class="text-[10px] font-bold text-slate-400 mb-6 uppercase">Match saved to database</p>
            <button onclick="location.reload()" class="w-full bg-slate-900 text-white py-4 rounded-xl font-black uppercase shadow-lg">New Match</button>
            <a href="history.php" class="text-[9px] font-black opacity-40 block mt-6 underline uppercase">View In Archive</a>
        </div>
    </div>

    <script>
        // TRACKING USER FROM SESSION
        const currentUser = "<?php echo $_SESSION['username']; ?>";

        let s = { scoreA: 0, scoreB: 0, setsA: 0, setsB: 0, toA: 2, toB: 2, subA: 6, subB: 6, format: 5, currentSet: 1, server: null, finished: false };
        let log = [], hist = [];

        async function sync(action, extra = {}) {
            const fd = new URLSearchParams();
            fd.append('action', action);
            fd.append('nameA', document.getElementById('nameA').value);
            fd.append('nameB', document.getElementById('nameB').value);
            fd.append('setsA', s.setsA);
            fd.append('setsB', s.setsB);
            for (let k in extra) fd.append(k, extra[k]);
            try { 
                const r = await fetch('api.php', { method: 'POST', body: fd });
                console.log("Synced:", await r.json());
            } catch (e) { console.error("Sync Error:", e); }
        }

        function resetMatch() { if(confirm("REALLY RESET ALL DATA?")) location.reload(); }
        function startMatch(f) { s.format = f; document.getElementById('initModal').classList.add('hidden'); update(); }
        function getTime() { return new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}); }

        function addPoint(t) {
            if (s.finished) return; 
            hist.push(JSON.stringify(s));
            s.server = t;
            const name = document.getElementById('name'+t).value.toUpperCase();
            if (t === 'A') s.scoreA++; else s.scoreB++;
            
            // Add entry with tracking
            log.push({ 
                set: s.currentSet, 
                time: getTime(), 
                event: name + ' POINT', 
                scoreA: s.scoreA, 
                scoreB: s.scoreB, 
                team: t,
                by: currentUser 
            });

            checkSetWin(t, name);
            update();
        }

        function checkSetWin(t, name) {
            const target = (s.currentSet === s.format) ? 15 : 25;
            if ((s.scoreA >= target || s.scoreB >= target) && Math.abs(s.scoreA - s.scoreB) >= 2) {
                const winMsg = `${name} WINS SET ${s.currentSet}`;
                log.push({ set: s.currentSet, time: getTime(), event: winMsg, scoreA: s.scoreA, scoreB: s.scoreB, team: 'SYSTEM', by: currentUser });
                
                if (t === 'A') s.setsA++; else s.setsB++;
                
                if (s.setsA === Math.ceil(s.format/2) || s.setsB === Math.ceil(s.format/2)) { 
                    s.finished = true; finish(name); 
                } else {
                    document.getElementById('setWinnerMsg').innerText = winMsg;
                    document.getElementById('nextSetModal').classList.remove('hidden');
                }
            }
        }

        function confirmNextSet() {
            s.scoreA = 0; s.scoreB = 0; s.toA = 2; s.toB = 2; s.subA = 6; s.subB = 6; s.currentSet++;
            document.getElementById('nextSetModal').classList.add('hidden');
            update();
        }

        function recordAction(type, t) {
            const k = (type === 'TO' ? 'to' : 'sub') + t;
            if (s[k] > 0) { 
                hist.push(JSON.stringify(s)); s[k]--;
                log.push({ set: s.currentSet, time: getTime(), event: `${type} - TEAM ${t}`, scoreA: s.scoreA, scoreB: s.scoreB, team: 'SYSTEM', by: currentUser });
                update();
            }
        }

        function update() {
            const target = (s.currentSet === s.format) ? 15 : 25;
            document.getElementById('scoreA').innerText = s.scoreA.toString().padStart(2,'0');
            document.getElementById('scoreB').innerText = s.scoreB.toString().padStart(2,'0');
            document.getElementById('setsA').innerText = s.setsA;
            document.getElementById('setsB').innerText = s.setsB;
            document.getElementById('setInfo').innerText = 'SET ' + s.currentSet;
            document.getElementById('remA').innerText = 'TO WIN: ' + Math.max(0, target - s.scoreA);
            document.getElementById('remB').innerText = 'TO WIN: ' + Math.max(0, target - s.scoreB);
            document.getElementById('numToA').innerText = s.toA; document.getElementById('numSubA').innerText = s.subA;
            document.getElementById('numToB').innerText = s.toB; document.getElementById('numSubB').innerText = s.subB;
            document.getElementById('srvA').classList.toggle('hidden', s.server !== 'A');
            document.getElementById('srvB').classList.toggle('hidden', s.server !== 'B');
            document.getElementById('cardA').classList.toggle('serving', s.server === 'A');
            document.getElementById('cardB').classList.toggle('serving', s.server === 'B');
        }

        function undo() { if (hist.length > 0) { s = JSON.parse(hist.pop()); log.pop(); update(); } }

        function finish(w) {
            document.getElementById('finalWinnerUI').innerText = w + " WINS MATCH";
            document.getElementById('finishModal').classList.remove('hidden');
            
            // Save to DB
            sync('save_match', { 
                matchLog: JSON.stringify(log),
                matchTitle: document.getElementById('matchID').value 
            });
        }
    </script>
</body>
</html>
