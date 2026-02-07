<?php
require_once 'db.php';
include 'includes/header.php';

// Handle Add Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'];
    $date = $_POST['event_date'];
    $desc = $_POST['description'];

    $stmt = $db->prepare("INSERT INTO planner (title, event_date, description) VALUES (?, ?, ?)");
    $stmt->execute([$title, $date, $desc]);
    header("Location: planner.php");
    exit;
}

// Handle Delete Event
if (isset($_GET['delete_id'])) {
    $stmt = $db->prepare("DELETE FROM planner WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: planner.php");
    exit;
}

// Fetch Events for calendar
$events_sql = $db->query("SELECT * FROM planner")->fetchAll();
$calendar_events = [];
foreach ($events_sql as $e) {
    $calendar_events[] = [
        'id' => $e['id'],
        'title' => $e['title'],
        'start' => $e['event_date'],
        'description' => $e['description'],
        'color' => '#4f46e5'
    ];
}
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<style>
    .fc-event {
        cursor: pointer;
        padding: 2px 5px;
    }

    .fc-toolbar-title {
        font-size: 1.5rem !important;
        font-weight: bold;
    }

    .fc-button-primary {
        background-color: var(--primary) !important;
        border-color: var(--primary) !important;
    }

    #calendar {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: var(--shadow);
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="margin:0;">📅 Parish Planner & Calendar</h2>
        <p style="margin:0; color: var(--secondary);">Schedule and manage parish events</p>
    </div>
    <button onclick="document.getElementById('eventModal').style.display='flex'" class="btn btn-primary">+ Add New
        Event</button>
</div>

<div class="grid" style="grid-template-columns: 1fr;">
    <div id='calendar'></div>
</div>

<!-- Add Event Modal -->
<div id="eventModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 500px; position: relative;">
        <button onclick="document.getElementById('eventModal').style.display='none'"
            style="position: absolute; top: 1rem; right: 1rem; border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h3>Add New Event</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Event Title</label>
                <input type="text" name="title" required placeholder="e.g., Sunday Mass, Parish Feast">
            </div>
            <div class="form-group">
                <label>Date & Time</label>
                <input type="datetime-local" name="event_date" required>
            </div>
            <div class="form-group">
                <label>Details / Description</label>
                <textarea name="description" rows="3" placeholder="Additional details about the event..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Save Event</button>
        </form>
    </div>
</div>

<!-- Event Detail Modal -->
<div id="detailModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="card" style="width: 100%; max-width: 450px; position: relative;">
        <button onclick="document.getElementById('detailModal').style.display='none'"
            style="position: absolute; top: 1rem; right: 1rem; border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h3 id="m_title">Event Details</h3>
        <div style="margin-bottom: 1rem;">
            <strong style="color: var(--secondary); font-size: 0.8rem; text-transform: uppercase;">When:</strong>
            <p id="m_date" style="margin: 0.25rem 0 1rem 0; font-weight: 600;"></p>

            <strong style="color: var(--secondary); font-size: 0.8rem; text-transform: uppercase;">Details:</strong>
            <p id="m_desc" style="margin: 0.25rem 0 1rem 0; color: #4b5563;"></p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="document.getElementById('detailModal').style.display='none'" class="btn btn-secondary"
                style="flex: 1;">Close</button>
            <a id="m_delete" href="#" class="btn btn-danger"
                style="flex: 1; text-align: center; text-decoration: none; background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;"
                onclick="return confirm('Delete this event?')">Delete Event</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: <?php echo json_encode($calendar_events); ?>,
            eventClick: function (info) {
                document.getElementById('m_title').innerText = info.event.title;
                document.getElementById('m_date').innerText = info.event.start.toLocaleString();
                document.getElementById('m_desc').innerText = info.event.extendedProps.description || 'No detailed description provided.';
                document.getElementById('m_delete').href = 'planner.php?delete_id=' + info.event.id;
                document.getElementById('detailModal').style.display = 'flex';
            }
        });
        calendar.render();
    });

    // Close modals on outside click
    window.onclick = function (event) {
        if (event.target == document.getElementById('eventModal')) {
            document.getElementById('eventModal').style.display = 'none';
        }
        if (event.target == document.getElementById('detailModal')) {
            document.getElementById('detailModal').style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>