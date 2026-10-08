<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

$q = trim($_GET['q'] ?? '');
$grade = trim($_GET['grade'] ?? '');

$sql = "
  SELECT u.id, u.full_name, sp.grade
  FROM users u
  LEFT JOIN student_profiles sp ON sp.user_id=u.id
  WHERE u.role='student'
";

$params = [];
$types = "";

if($q !== ""){
  $sql .= " AND u.full_name LIKE ? ";
  $params[] = "%$q%";
  $types .= "s";
}

if($grade !== ""){
  $sql .= " AND sp.grade=? ";
  $params[] = $grade;
  $types .= "s";
}

$sql .= " ORDER BY sp.grade+0 ASC, u.full_name ASC";

$stmt = $conn->prepare($sql);
if($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card card-soft p-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <h4 class="fw-bold mb-0">Student Subjects</h4>
      <div class="text-muted small">Shows subjects based on student grade.</div>
    </div>
    <a class="btn btn-outline-dark btn-sm" href="/classms/teacher/subjects.php">Back</a>
  </div>

  <form method="get" class="row g-2 mb-3">
    <div class="col-md-7">
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search student name...">
    </div>
    <div class="col-md-3">
      <input class="form-control" name="grade" value="<?= e($grade) ?>" placeholder="Grade (6-12)">
    </div>
    <div class="col-md-2">
      <button class="btn btn-dark w-100">Search</button>
    </div>
  </form>

  <?php if($res->num_rows === 0): ?>
    <div class="alert alert-info mb-0">No students found.</div>
  <?php endif; ?>

  <div class="row g-3">
    <?php while($r = $res->fetch_assoc()):
      $sid = (int)$r['id'];
      $gr = (string)($r['grade'] ?? '');

      $sub = $conn->prepare("SELECT name FROM subjects WHERE grade=? AND status='approved' ORDER BY name ASC LIMIT 9");
      $sub->bind_param("s", $gr);
      $sub->execute();
      $subRes = $sub->get_result();

      $names = [];
      while($s = $subRes->fetch_assoc()) $names[] = $s['name'];
    ?>
      <div class="col-lg-6">
        <div class="border rounded-3 p-3 bg-white">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-bold"><?= e($r['full_name']) ?></div>
              <div class="text-muted small">Student ID: <?= $sid ?> | Grade: <b><?= e($gr ?: '-') ?></b></div>
            </div>
            <a class="btn btn-sm btn-dark" href="/classms/teacher/term_marks.php?student_id=<?= $sid ?>">Enter Marks</a>
          </div>

          <div class="mt-2 small fw-bold">Approved Subjects (9)</div>
          <?php if(count($names) > 0): ?>
            <div class="small text-muted"><?= e(implode(" • ", $names)) ?></div>
          <?php else: ?>
            <div class="small text-muted">No approved subjects found for this grade.</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>

</div>

<style>
.card-soft{ border:1px solid rgba(0,0,0,.08); border-radius:16px; }
</style>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
