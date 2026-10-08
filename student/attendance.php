<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("student");

date_default_timezone_set("Asia/Colombo");

$uid = (int) current_user()['id'];

/* -------------------------
   Month selector (YYYY-MM)
--------------------------*/
$month = trim($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
  $month = date('Y-m');
}

$firstDay = $month . "-01";
$firstTs  = strtotime($firstDay);
$daysInMonth = (int) date('t', $firstTs);
$monthName = date('F Y', $firstTs);

/* First weekday (Mon=1 ... Sun=7) */
$firstWeekday = (int) date('N', $firstTs);

/* Previous / Next month */
$prevMonth = date('Y-m', strtotime('-1 month', $firstTs));
$nextMonth = date('Y-m', strtotime('+1 month', $firstTs));

/* -------------------------
   Load attendance for month
--------------------------*/
$start = $firstDay;
$end   = date('Y-m-t', $firstTs); // last day

$q = $conn->prepare("
  SELECT attendance_date, status, marked_at, note
  FROM attendance
  WHERE student_id=?
    AND attendance_date BETWEEN ? AND ?
  ORDER BY attendance_date ASC
");
$q->bind_param("iss", $uid, $start, $end);
$q->execute();
$res = $q->get_result();

/* Map by date for easy calendar lookup */
$byDate = [];
while ($r = $res->fetch_assoc()) {
  $byDate[$r['attendance_date']] = $r;
}

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card card-soft p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="fw-bold mb-1">My Attendance Calendar</h4>
      <div class="text-muted small">Month view (Present / Absent) + time + note</div>
    </div>
    <a class="btn btn-outline-dark btn-sm" href="/classms/student/dashboard.php">Back</a>
  </div>

  <!-- Month controls -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-outline-dark btn-sm" href="?month=<?= e($prevMonth) ?>">&larr; Prev</a>
      <div class="fw-bold"><?= e($monthName) ?></div>
      <a class="btn btn-outline-dark btn-sm" href="?month=<?= e($nextMonth) ?>">Next &rarr;</a>
    </div>

    <form method="get" class="d-flex align-items-center gap-2">
      <label class="text-muted small mb-0">Go to:</label>
      <input type="month" class="form-control form-control-sm" name="month" value="<?= e($month) ?>">
      <button class="btn btn-dark btn-sm">View</button>
    </form>
  </div>

  <!-- Legend -->
  <div class="d-flex gap-2 flex-wrap mb-3">
    <span class="legend-pill legend-present">Present</span>
    <span class="legend-pill legend-absent">Absent</span>
    <span class="legend-pill legend-none">No record</span>
  </div>

  <!-- Calendar -->
  <div class="calendar">
    <div class="cal-head">Mon</div>
    <div class="cal-head">Tue</div>
    <div class="cal-head">Wed</div>
    <div class="cal-head">Thu</div>
    <div class="cal-head">Fri</div>
    <div class="cal-head">Sat</div>
    <div class="cal-head">Sun</div>

    <?php
      // Empty cells before 1st day
      for ($i = 1; $i < $firstWeekday; $i++) {
        echo "<div class='cal-cell cal-empty'></div>";
      }

      // Days of month
      for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = sprintf("%s-%02d", $month, $d);
        $isToday = ($date === date('Y-m-d'));

        $record = $byDate[$date] ?? null;
        $status = $record['status'] ?? null;

        $cls = "cal-cell";
        if ($status === 'present') $cls .= " cal-present";
        elseif ($status === 'absent') $cls .= " cal-absent";
        else $cls .= " cal-none";
        if ($isToday) $cls .= " cal-today";

        $time = $record ? date('H:i', strtotime($record['marked_at'])) : '';
        $note = $record['note'] ?? '';
        $tooltip = $record
          ? "Status: " . $record['status'] . "\nTime: " . $record['marked_at'] . "\nNote: " . ($note ?: '-')
          : "No record";

        echo "<div class='{$cls}' title='".e($tooltip)."'>
                <div class='cal-day'>{$d}</div>";

        if ($record) {
          echo "<div class='cal-meta'>
                  <span class='cal-badge'>".e($record['status'])."</span>
                  <div class='cal-time'>".e($time)."</div>
                </div>";
          if ($note) {
            echo "<div class='cal-note'>".e($note)."</div>";
          }
        } else {
          echo "<div class='cal-meta'><div class='cal-time text-muted'>—</div></div>";
        }

        echo "</div>";
      }
    ?>
  </div>

  <!-- Table (optional below calendar) -->
  <div class="mt-4">
    <div class="fw-bold mb-2">This Month Records</div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th>Date</th>
            <th>Status</th>
            <th>Time</th>
            <th>Note</th>
          </tr>
        </thead>
        <tbody>
          <?php if(count($byDate) > 0): ?>
            <?php foreach($byDate as $dt => $r): ?>
              <tr>
                <td><?= e($dt) ?></td>
                <td>
                  <span class="badge <?= $r['status']==='present' ? 'text-bg-success' : 'text-bg-danger' ?>">
                    <?= e($r['status']) ?>
                  </span>
                </td>
                <td><?= e($r['marked_at']) ?></td>
                <td><?= e($r['note'] ?? '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4" class="text-center text-muted">No attendance records in this month.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<style>
  .card-soft{ border:1px solid rgba(0,0,0,.08); border-radius:16px; }

  .legend-pill{
    padding:.25rem .6rem;
    border-radius:999px;
    font-size:.85rem;
    border:1px solid rgba(0,0,0,.08);
    background:#fff;
  }
  .legend-present{ border-left:6px solid #1cc88a; }
  .legend-absent{ border-left:6px solid #e74a3b; }
  .legend-none{ border-left:6px solid #cbd5e1; }

  .calendar{
    display:grid;
    grid-template-columns: repeat(7, 1fr);
    gap:10px;
  }
  .cal-head{
    font-weight:700;
    text-align:center;
    padding:8px 0;
    background:#111827;
    color:#fff;
    border-radius:10px;
    font-size:.9rem;
  }
  .cal-cell{
    min-height:110px;
    border-radius:14px;
    padding:10px;
    border:1px solid rgba(0,0,0,.08);
    background:#fff;
    position:relative;
    overflow:hidden;
  }
  .cal-empty{ background:transparent; border:none; }

  .cal-day{
    font-weight:800;
    font-size:1.05rem;
    line-height:1;
  }
  .cal-meta{
    margin-top:8px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
  }
  .cal-badge{
    font-size:.75rem;
    padding:.15rem .5rem;
    border-radius:999px;
    border:1px solid rgba(0,0,0,.08);
    background:#f8fafc;
    text-transform:capitalize;
  }
  .cal-time{ font-size:.8rem; color:#6b7280; }

  .cal-note{
    margin-top:8px;
    font-size:.78rem;
    color:#374151;
    white-space:nowrap;
    text-overflow:ellipsis;
    overflow:hidden;
  }

  .cal-present{ border-left:7px solid #1cc88a; }
  .cal-absent{ border-left:7px solid #e74a3b; }
  .cal-none{ border-left:7px solid #cbd5e1; }

  .cal-today{
    outline:2px dashed rgba(17,24,39,.5);
    outline-offset:2px;
  }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
