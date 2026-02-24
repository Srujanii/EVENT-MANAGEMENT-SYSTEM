<?php
// admin.php – Christ Events 2026 | Registration Handler + Admin Panel
$conn=new mysqli("localhost","root","","event_registration");
if($conn->connect_error)die("Connection Failed");
$conn->set_charset('utf8mb4');

if($_SERVER['REQUEST_METHOD']==='POST'){
    $n=trim($_POST['participant_name']??'');$e=trim($_POST['email']??'');
    $p=trim($_POST['phone']??'');$c=trim($_POST['college']??'');
    $ei=trim($_POST['event_id']??'');$amt=(float)($_POST['amount']??0);
    $pm=trim($_POST['payment_method']??'online');
    if(!$n||!$e||!$p||!$ei)die("Error: Required fields missing");
    if(!filter_var($e,FILTER_VALIDATE_EMAIL))die("Error: Invalid email");
    $conn->begin_transaction();
    try{
        // Look up existing participant
        $s=$conn->prepare("SELECT user_id FROM participants WHERE email=?");
        if(!$s)throw new Exception("DB error (P1): ".$conn->error);
        $s->bind_param("s",$e);$s->execute();$r=$s->get_result();
        $uid=$r->num_rows>0?$r->fetch_assoc()['user_id']:null;
        $s->close();

        // Create participant if not found
        if(!$uid){
            $s=$conn->prepare("INSERT INTO participants(name,email,phone,college)VALUES(?,?,?,?)");
            if(!$s)throw new Exception("DB error (P2): ".$conn->error);
            $s->bind_param("ssss",$n,$e,$p,$c);$s->execute();$uid=$conn->insert_id;
            $s->close();
        }

        // Check for duplicate registration using JOIN instead of subquery
        $s=$conn->prepare("SELECT r.reg_id FROM registrations r JOIN events e ON r.event_id=e.event_id WHERE r.user_id=? AND e.event_id_code=?");
        if(!$s)throw new Exception("DB error (P3): ".$conn->error);
        $s->bind_param("is",$uid,$ei);$s->execute();
        if($s->get_result()->num_rows>0){$conn->rollback();die("Error: Already registered");}
        $s->close();

        // Insert registration
        $rc="REG".date('Ymd').rand(1000,9999);
        $s=$conn->prepare("INSERT INTO registrations(user_id,event_id,event_id_code,reg_id_code)SELECT ?,event_id,?,? FROM events WHERE event_id_code=?");
        if(!$s)throw new Exception("DB error (P4): ".$conn->error);
        $s->bind_param("isss",$uid,$ei,$rc,$ei);$s->execute();
        $rid=$conn->insert_id;
        $s->close();
        if(!$rid)throw new Exception("Registration insert failed – check event code.");

        // Insert payment if amount provided
        if($amt>0){
            $pid="PAY".date('YmdHis').rand(100,999);
            $s=$conn->prepare("INSERT INTO payments(reg_id,amount,payment_id,payment_method)VALUES(?,?,?,?)");
            if(!$s)throw new Exception("DB error (P5): ".$conn->error);
            $s->bind_param("idss",$rid,$amt,$pid,$pm);$s->execute();$s->close();
        }
        $conn->commit();echo "✅ Registration Successful! Your ID: <strong>".htmlspecialchars($rc)."</strong>";
    }catch(Exception $ex){$conn->rollback();die("Error: ".$ex->getMessage());}
    $conn->close();exit;
}

$f=$_GET['q']??'';$ef=$_GET['eventId']??'';$tab=$_GET['tab']??'reg';$ex=$_GET['export']??'';

// Sessions
$sess=[];$sq=$conn->query("SELECT s.*,e.name ev,e.event_id_code code FROM event_sessions s JOIN events e ON s.event_id=e.event_id ORDER BY e.event_id,s.session_id");
while($r=$sq->fetch_assoc())$sess[]=$r;

// Registrations
$sql="SELECT r.reg_id,r.reg_id_code,r.reg_date,p.name,p.email,p.phone,p.college,e.name ev,e.event_id_code code,e.date edate,e.time,e.venue,py.amount,py.payment_id,py.payment_method FROM registrations r JOIN participants p ON r.user_id=p.user_id JOIN events e ON r.event_id=e.event_id LEFT JOIN payments py ON r.reg_id=py.reg_id";
$w=[];$pr=[];$t='';
if($ef){$w[]="e.event_id_code=?";$t.='s';$pr[]=$ef;}
if($f){$w[]="(e.name LIKE ? OR p.name LIKE ? OR p.email LIKE ?)";$t.='sss';$l="%$f%";$pr[]=$l;$pr[]=$l;$pr[]=$l;}
if($w)$sql.=' WHERE '.implode(' AND ',$w);
$sql.=' ORDER BY r.reg_id DESC';
$st=$conn->prepare($sql);
if($pr){$st->bind_param($t,...$pr);}
$st->execute();$res=$st->get_result();
if($ex==='csv'){
    header('Content-Type:text/csv');header('Content-Disposition:attachment;filename=reg_'.date('Ymd').'.csv');
    $o=fopen('php://output','w');
    fputcsv($o,['ID','Code','Name','Email','Phone','College','Event','Event Code','Date','Time','Venue','Amount','Payment ID','Method','Registered At']);
    while($r=$res->fetch_assoc())fputcsv($o,array_values($r));
    fclose($o);exit;
}
$rows=$res->fetch_all(MYSQLI_ASSOC);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Admin – Christ Events</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<style>
:root{--bg:#06102b;--s:rgba(255,255,255,.04);--b:rgba(255,255,255,.09);--gold:#f5a623;--blue:#3b82f6;--v:#818cf8;--t:#e8eaf6;--m:#7986a8}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--bg);color:var(--t);font-family:'Outfit',sans-serif;min-height:100vh}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 10% 0%,rgba(59,130,246,.15),transparent 55%),radial-gradient(ellipse 50% 50% at 90% 100%,rgba(129,140,248,.1),transparent 55%);pointer-events:none;z-index:0}
.wrap{max-width:1600px;margin:0 auto;padding:2.5rem;position:relative;z-index:1}
h1{font-family:'Playfair Display',serif;font-size:2.2rem;background:linear-gradient(135deg,var(--blue),var(--v));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:.2rem}
.sub{font-size:.8rem;color:var(--m);margin-bottom:2rem}
.tabs{display:flex;gap:.5rem;margin-bottom:1.5rem}
.tab{padding:.5rem 1.2rem;border-radius:8px;border:1px solid var(--b);background:var(--s);color:var(--m);text-decoration:none;font-size:.82rem;font-weight:600;transition:all .2s}
.tab.on,.tab:hover{background:linear-gradient(135deg,var(--blue),var(--v));color:#fff;border-color:transparent}
.ctrl{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.2rem;align-items:center}
input{padding:.5rem .9rem;border-radius:8px;border:1px solid var(--b);background:rgba(255,255,255,.05);color:var(--t);font-size:.85rem;outline:none;font-family:'Outfit',sans-serif;transition:border-color .2s}
input:focus{border-color:var(--blue)}
.btn{padding:.5rem 1.2rem;border-radius:8px;background:linear-gradient(135deg,var(--blue),var(--v));color:#fff;text-decoration:none;border:none;cursor:pointer;font-size:.82rem;font-weight:600;transition:opacity .2s;font-family:'Outfit',sans-serif}.btn:hover{opacity:.85}
.btn-r{background:rgba(255,255,255,.07);color:var(--m)}
.btn-csv{margin-left:auto;background:linear-gradient(135deg,var(--gold),#fdd47f);color:#06102b;font-weight:700}
.cnt{font-size:.8rem;color:var(--m);margin-bottom:.8rem}
.tw{overflow-x:auto;border-radius:14px;border:1px solid var(--b)}
table{width:100%;border-collapse:collapse}
th{background:rgba(59,130,246,.08);padding:.7rem 1rem;text-align:left;font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--m);white-space:nowrap;font-weight:600}
td{padding:.65rem 1rem;border-bottom:1px solid rgba(255,255,255,.04);font-size:.82rem;white-space:nowrap}
tr:last-child td{border-bottom:none}tr:hover td{background:rgba(59,130,246,.05)}
.badge{background:rgba(59,130,246,.15);padding:.2rem .55rem;border-radius:6px;font-size:.7rem;color:#93c5fd;font-weight:600}
.badge-g{background:rgba(245,166,35,.12);color:#fcd34d}
.badge-v{background:rgba(129,140,248,.15);color:#c4b5fd}
.green{color:#4ade80;font-weight:600}.dim{color:var(--m)}
.empty{padding:3rem;text-align:center;color:var(--m);font-style:italic}
</style>
</head>
<body>
<div class="wrap">
<h1>🎓 Admin Panel</h1>
<p class="sub">Christ University — Tech Events 2026</p>
<div class="tabs">
  <a href="?tab=reg" class="tab <?=$tab==='reg'?'on':''?>">📋 Registrations (<?=count($rows)?>)</a>
  <a href="?tab=sess" class="tab <?=$tab==='sess'?'on':''?>">🗓 Sessions (<?=count($sess)?>)</a>
</div>
<?php if($tab==='sess'):?>
<div class="tw"><table>
<thead><tr><th>#</th><th>Event</th><th>Code</th><th>Session</th><th>Start</th><th>End</th><th>Room</th></tr></thead>
<tbody>
<?php if(!$sess):?><tr><td colspan="7" class="empty">No sessions.</td></tr>
<?php else:foreach($sess as $s):?>
<tr><td class="dim"><?=$s['session_id']?></td><td><?=htmlspecialchars($s['ev'])?></td><td><span class="badge-g badge"><?=htmlspecialchars($s['code'])?></span></td><td><span class="badge badge-v"><?=htmlspecialchars($s['session_name'])?></span></td><td><?=htmlspecialchars($s['time_start'])?></td><td><?=htmlspecialchars($s['time_end'])?></td><td><?=htmlspecialchars($s['room_number'])?></td></tr>
<?php endforeach;endif;?>
</tbody></table></div>
<?php else:?>
<form method="get" class="ctrl">
  <input type="hidden" name="tab" value="reg">
  <input name="q" placeholder="Search name / email" value="<?=htmlspecialchars($f)?>">
  <input name="eventId" placeholder="Event Code (101–110)" value="<?=htmlspecialchars($ef)?>" style="width:170px">
  <button class="btn">Filter</button>
  <a href="?tab=reg" class="btn btn-r">Reset</a>
  <a href="?<?=http_build_query(array_merge($_GET,['export'=>'csv','tab'=>'reg']))?>" class="btn btn-csv">⬇ Export CSV</a>
</form>
<p class="cnt">Showing <strong><?=count($rows)?></strong> registration<?=count($rows)!==1?'s':''?></p>
<div class="tw"><table>
<thead><tr><th>#</th><th>Code</th><th>Name</th><th>Email</th><th>Phone</th><th>College</th><th>Event</th><th>Code</th><th>Date</th><th>Time</th><th>Venue</th><th>Amount</th><th>Payment ID</th><th>Method</th><th>Registered At</th></tr></thead>
<tbody>
<?php if(!$rows):?><tr><td colspan="15" class="empty">No registrations found.</td></tr>
<?php else:foreach($rows as $r):?>
<tr>
<td class="dim"><?=$r['reg_id']?></td>
<td><span class="badge"><?=htmlspecialchars($r['reg_id_code'])?></span></td>
<td><?=htmlspecialchars($r['name'])?></td>
<td class="dim"><?=htmlspecialchars($r['email'])?></td>
<td class="dim"><?=htmlspecialchars($r['phone'])?></td>
<td><?=htmlspecialchars($r['college'])?></td>
<td><?=htmlspecialchars($r['ev'])?></td>
<td><span class="badge badge-g"><?=htmlspecialchars($r['code'])?></span></td>
<td><?=htmlspecialchars($r['edate'])?></td>
<td class="dim"><?=htmlspecialchars($r['time'])?></td>
<td><?=htmlspecialchars($r['venue'])?></td>
<td class="green">₹<?=number_format($r['amount']??0,2)?></td>
<td class="dim"><?=htmlspecialchars($r['payment_id']??'N/A')?></td>
<td class="dim"><?=htmlspecialchars($r['payment_method']??'N/A')?></td>
<td class="dim"><?=htmlspecialchars($r['reg_date'])?></td>
</tr>
<?php endforeach;endif;?>
</tbody></table></div>
<?php endif;?>
</div>
<?php if(isset($st))$st->close();$conn->close();?>
</body></html>