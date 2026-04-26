<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit; }
require 'db.php';
$is_admin = ($_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>V-ELITE v5.7</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=JetBrains+Mono:wght@700&display=swap');
        :root { --bg: #020617; --card: rgba(30, 41, 59, 0.7); --text: #f8fafc; --border: rgba(255, 255, 255, 0.1); }
        .light-theme { --bg: #f8fafc; --card: rgba(255, 255, 255, 0.9); --text: #0f172a; --border: rgba(0, 0, 0, 0.1); }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text); min-height: 100dvh; text-transform: uppercase; display: flex; flex-direction: column; overflow: hidden; }
        .glass { background: var(--card); backdrop-filter: blur(10px); border: 1px solid var(--border); border-radius: 1.2rem; }
        .score-font { font-family: 'JetBrains Mono', monospace; line-height: 1; }
        .serving { outline: 3px solid #3b82f6; box-shadow: 0 0 15px rgba(59, 130, 246, 0.4); }
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
        </div>
    </div>

    <div id="nextSetModal" class="fixed inset-0 z-[110] bg-slate-950/95 backdrop-blur-xl flex items-center justify-center p-6 hidden no-print">
        <div class="glass p-8 w-full max-w-xs text-center border-blue-500 border-2">
            <h2 id="setWinnerMsg" class="text-xl font-black mb-6 text-white leading-tight"></h2>
            <button onclick="confirmNextSet()" class="w-full bg-blue-600 py-5 rounded-xl font-black text-white">START NEXT SET</button>
        </div>
    </div>

    <header class="flex justify-between items-center px-4 py-2 glass no-print shrink-0">
        <div class="leading-tight">
            <input type="text" id="matchID" value="OFFICIAL MATCH" class="bg-transparent font-black text-blue-500 text-[10px] w-28 outline-none uppercase">
            <div id="setInfo" class="text-[9px] font-bold opacity-50 uppercase">SET 1</div>
        </div>
        <div class="flex gap-2 items-center">
            <?php if($is_admin): ?>
                <a href="users.php" title="Manage Users" class="w-8 h-8 flex items-center justify-center glass text-[10px]">⚙️</a>
            <?php endif; ?>
            <a href="history.php" class="w-8 h-8 flex items-center justify-center glass text-[10px]">📜</a>
            <button onclick="undo()" class="px-3 h-8 glass text-[9px] font-bold">UNDO</button>
            <a href="logout.php" class="flex items-center justify-center px-3 h-8 glass text-slate-400 text-[9px] font-black uppercase tracking-widest">EXIT</a>
        </div>
    </header>

    <main class="flex-grow flex flex-col gap-2 no-print min-h-0">
        <div id="cardA" class="glass border-l-4 border-blue-600 flex-1 flex flex-col justify-center px-4 py-2">
            <div class="flex justify-between items-center mb-1">
                <input type="text" id="nameA" value="HOME" class="bg-transparent font-black text-[10px] outline-none w-3/4">
                <div id="srvA" class="w-3 h-3 rounded-full bg-yellow-400 hidden"></div>
            </div>
            <div class="flex justify-between items-center">
                <span id="scoreA" class="score-font text-6xl tracking-tighter">00</span>
                <div class="text-right">
                    <div id="setsA" class="text-3xl font-black text-blue-500">0</div>
                    <div id="remA" class="text-[7px] font-bold opacity-40">TO WIN: 25</div>
                </div>
            </div>
        </div>
        <div id="cardB" class="glass border-l-4 border-orange-600 flex-1 flex flex-col justify-center px-4 py-2">
            <div class="flex justify-between items-center mb-1">
                <input type="text" id="nameB" value="GUEST" class="bg-transparent font-black text-[10px] outline-none w-3/4">
                <div id="srvB" class="w-3 h-3 rounded-full bg-yellow-400 hidden"></div>
            </div>
            <div class="flex justify-between items-center">
                <span id="scoreB" class="score-font text-6xl tracking-tighter">00</span>
                <div class="text-right">
                    <div id="setsB" class="text-3xl font-black text-orange-500">0</div>
                    <div id="remB" class="text-[7px] font-bold opacity-40">TO WIN: 25</div>
                </div>
            </div>
        </div>
    </main>

    <footer class="grid grid-cols-2 gap-2 h-20 shrink-0 no-print">
        <button onclick="addPoint('A')" class="bg-blue-600 rounded-xl font-black text-white text-base active:scale-95 transition-all">POINT A</button>
        <button onclick="addPoint('B')" class="bg-orange-600 rounded-xl font-black text-white text-base active:scale-95 transition-all">POINT B</button>
    </footer>

    <div id="finishModal" class="fixed inset-0 z-[200] bg-slate-950 flex items-center justify-center p-6 hidden no-print">
        <div class="glass bg-white text-slate-950 p-8 w-full max-w-xs text-center shadow-2xl">
            <h2 id="finalWinnerUI" class="text-xl font-black mb-6 italic uppercase"></h2>
            <button onclick="location.reload()" class="w-full bg-slate-900 text-white py-4 rounded-xl font-black mb-4 uppercase">New Match</button>
            <a href="history.php" class="text-[9px] font-bold opacity-40 block mx-auto underline">GOTO ARCHIVE</a>
        </div>
    </div>

    <script>
        const currentUser = "<?php echo $_SESSION['username']; ?>";
        let s = { scoreA: 0, scoreB: 0, setsA: 0, setsB: 0, format: 5, currentSet: 1, server: null, finished: false };
        let log = [], hist = [];

        async function sync(action, extra = {}) {
            const fd = new URLSearchParams();
            fd.append('action', action);
            fd.append('nameA', document.getElementById('nameA').value);
            fd.append('nameB', document.getElementById('nameB').value);
            fd.append('setsA', s.setsA); fd.append('setsB', s.setsB);
            for (let k in extra) fd.append(k, extra[k]);
            try { await fetch('api.php', { method: 'POST', body: fd }); } catch (e) { console.error(e); }
        }

        function startMatch(f) { s.format = f; document.getElementById('initModal').classList.add('hidden'); update(); }
        function getTime() { return new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}); }

        function addPoint(t) {
            if (s.finished) return; 
            hist.push(JSON.stringify(s));
            s.server = t;
            const name = document.getElementById('name'+t).value.toUpperCase();
            if (t === 'A') s.scoreA++; else s.scoreB++;
            
            log.push({ set: s.currentSet, time: getTime(), event: name + ' POINT', scoreA: s.scoreA, scoreB: s.scoreB, team: t, by: currentUser });
            
            const target = (s.currentSet === s.format) ? 15 : 25;
            if ((s.scoreA >= target || s.scoreB >= target) && Math.abs(s.scoreA - s.scoreB) >= 2) {
                const winMsg = `${name} WINS SET ${s.currentSet}`;
                log.push({ set: s.currentSet, time: '---', event: winMsg, scoreA: s.scoreA, scoreB: s.scoreB, team: 'WIN', by: currentUser });
                if (t === 'A') s.setsA++; else s.setsB++;
                if (s.setsA === Math.ceil(s.format/2) || s.setsB === Math.ceil(s.format/2)) { s.finished = true; finish(name); }
                else { document.getElementById('setWinnerMsg').innerText = winMsg; document.getElementById('nextSetModal').classList.remove('hidden'); }
            }
            update();
        }

        function confirmNextSet() {
            s.scoreA = 0; s.scoreB = 0; s.currentSet++;
            document.getElementById('nextSetModal').classList.add('hidden'); update();
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
            document.getElementById('srvA').classList.toggle('hidden', s.server !== 'A');
            document.getElementById('srvB').classList.toggle('hidden', s.server !== 'B');
            document.getElementById('cardA').classList.toggle('serving', s.server === 'A');
            document.getElementById('cardB').classList.toggle('serving', s.server === 'B');
        }

        function undo() { if (hist.length > 0) { s = JSON.parse(hist.pop()); log.pop(); update(); } }

        function finish(w) {
            document.getElementById('finalWinnerUI').innerText = w + " WINS MATCH";
            document.getElementById('finishModal').classList.remove('hidden');

            // CONSOLIDATED SINGLE SYNC CALL
            sync('save_match', { 
                matchLog: JSON.stringify(log),
                matchTitle: document.getElementById('matchID').value 
            });
        }
    </script>
</body>
</html>
