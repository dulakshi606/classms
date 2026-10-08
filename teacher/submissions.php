<?php
require_once __DIR__ . "/../includes/auth.php";
require_role("teacher");

$tid = (int) current_user()['id'];
$msg = "";

$assignment_id = (int)($_GET['assignment_id'] ?? 0);
if($assignment_id<=0){
  die("Assignment id required.");
}

// ✅ Load assignment + grade + subject, verify teacher owns it
$a = $conn->prepare("
  SELECT a.*, s.grade, s.name subject_name
  FROM assignments a
  JOIN subjects s ON s.id=a.subject_id
  WHERE a.id=? AND a.teacher_id=?
  LIMIT 1
");
$a->bind_param("ii",$assignment_id,$tid);
$a->execute();
$as = $a->get_result()->fetch_assoc();
if(!$as) die("Assignment not found or not yours.");

$grade = (string)$as['grade'];

/* ---------------------------
   SAVE MARKS + FEEDBACK
----------------------------*/
if(isset($_POST['save_grade'])){
  $student_id = (int)($_POST['student_id'] ?? 0);
  $marks = trim($_POST['marks'] ?? '');
  $feedback = trim($_POST['feedback'] ?? '');

  if($student_id <= 0){
    $msg="<div class='alert alert-danger'>Invalid student.</div>";
  } else {
    $marks_val = null;

    if($marks !== ''){
      if(!is_numeric($marks)){
        $msg="<div class='alert alert-danger'>Marks must be numeric.</div>";
      } else {
        $m=(float)$marks;
        if($m<0 || $m>100) $msg="<div class='alert alert-danger'>Marks must be 0-100.</div>";
        else $marks_val=$m;
      }
    }

    if($msg===''){
      // ✅ must exist submission
      $chk = $conn->prepare("SELECT id FROM submissions WHERE assignment_id=? AND student_id=? LIMIT 1");
      $chk->bind_param("ii",$assignment_id,$student_id);
      $chk->execute();
      $sub = $chk->get_result()->fetch_assoc();

      if(!$sub){
        $msg="<div class='alert alert-danger'>Student has not submitted yet. Cannot grade.</div>";
      } else {
        if($marks_val===null){
          $up=$conn->prepare("UPDATE submissions SET marks=NULL, feedback=?, seen_by_student=0 WHERE assignment_id=? AND student_id=?");
          $up->bind_param("sii",$feedback,$assignment_id,$student_id);
          $up->execute();
        } else {
          $up=$conn->prepare("UPDATE submissions SET marks=?, feedback=?, seen_by_student=0 WHERE assignment_id=? AND student_id=?");
          $up->bind_param("dsii",$marks_val,$feedback,$assignment_id,$student_id);
          $up->execute();
        }
        $msg="<div class='alert alert-success'>✅ Saved marks/feedback. Student will see it.</div>";
      }
    }
  }
}

/* ---------------------------
   LIST STUDENTS + SUBMISSION
   ✅ FIX: LEFT JOIN student_profiles
----------------------------*/
$q = trim($_GET['q'] ?? '');

$sql = "
  SELECT 
    u.id student_id, u.full_name, u.email,
    sp.grade AS profile_grade,
    sub.file_path, sub.submitted_at, sub.marks, sub.feedback
  FROM users u
  LEFT JOIN student_profiles sp ON sp.user_id=u.id
  LEFT JOIN submissions sub ON sub.student_id=u.id AND sub.assignment_id=?
  WHERE u.role='student'
    AND (sp.grade=?)
";

$params = [$assignment_id, $grade];
$types = "is";

if($q!==''){
  $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?) ";
  $like="%$q%";
  $params[]=$like; $params[]=$like;
  $types.="ss";
}

$sql .= " ORDER BY u.full_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

require_once __DIR__ . "/../includes/header.php";
?>

<div class="card card-soft p-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div>
      <h4 class="fw-bold mb-0">Student Submissions</h4>
      <div class="text-muted small">
        Assignment: <b><?= e($as['title']) ?></b> |
        Grade: <b><?= e($grade) ?></b> |
        Subject: <b><?= e($as['subject_name']) ?></b> |
        Due: <b><?= e($as['due_date']) ?></b>
      </div>
    </div>
    <a class="btn btn-outline-dark btn-sm" href="/classms/teacher/assignments.php">Back</a>
  </div>

  <?= $msg ?>

  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
    <div class="col-md-8">
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search student name/email...">
    </div>
    <div class="col-md-4 d-flex gap-2">
      <button class="btn btn-dark w-100">Search</button>
      <a class="btn btn-outline-dark w-100" href="/classms/teacher/submissions.php?assignment_id=<?= $assignment_id ?>">Reset</a>
    </div>
  </form>

  <?php if($res->num_rows===0): ?>
    <div class="alert alert-info mb-0">No students found for Grade <?= e($grade) ?>.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Status</th>
            <th>File</th>
            <th>Submitted At</th>
            <th style="width:320px;">Marks + Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($r=$res->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>

              <td>
                <div class="fw-semibold"><?= e($r['full_name']) ?></div>
                <div class="small text-muted">
                  <?= e($r['email']) ?> | ID: <?= (int)$r['student_id'] ?> | Grade: <?= e($r['profile_grade'] ?? '-') ?>
                </div>
              </td>

              <td>
                <?php if($r['file_path']): ?>
                  <span class="badge text-bg-success">Submitted</span>
                <?php else: ?>
                  <span class="badge text-bg-danger">Not Submitted</span>
                <?php endif; ?>
              </td>

              <td class="text-center">
                <?php if($r['file_path']): ?>
                  <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= e($r['file_path']) ?>">View</a>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>

              <td><?= e($r['submitted_at'] ?? '-') ?></td>

              <td>
                <?php if(!$r['file_path']): ?>
                  <div class="text-muted small">Student must submit to grade.</div>
                <?php else: ?>
                  <form method="post" class="row g-2">
                    <input type="hidden" name="student_id" value="<?= (int)$r['student_id'] ?>">

                    <div class="col-4">
                      <input class="form-control form-control-sm" name="marks"
                             value="<?= e($r['marks'] ?? '') ?>" placeholder="0-100">
                    </div>

                    <div class="col-8">
                      <input class="form-control form-control-sm" name="feedback"
                             value="<?= e($r['feedback'] ?? '') ?>" placeholder="Feedback...">
                    </div>

                    <div class="col-12">
                      <button class="btn btn-sm btn-dark" name="save_grade">Save</button>
                    </div>
                  </form>
                <?php endif; ?>
              </td>

            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<style>.card-soft{border:1px solid rgba(0,0,0,.08);border-radius:16px;}</style>
<?php require_once __DIR__ . "/../includes/footer.php"; ?>
