<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CategoryResults;
use App\Models\CategoryModel;
use App\Models\CriteriaModel;
use App\Models\ContestantModel;
use App\Models\EventModel;
use App\Models\JudgeAssignmentModel;
use App\Models\ResultModel;
use App\Models\RubricTemplateModel;
use App\Models\ScoreModel;
use App\Models\UserModel;

class AdminController extends Controller {

    public function index() {
        Auth::requireRole('admin');
        $events = EventModel::getWithCounts();
        $categories = CategoryModel::getAllWithProgress();
        $criteria = CriteriaModel::getAllWithDetails();
        $contestants = ContestantModel::getAll();
        $judges = UserModel::getAllJudges();
        $assignments = JudgeAssignmentModel::getAllWithDetails();

        $stats = [
            'events_total' => count($events),
            'live_events' => EventModel::countByStatus('live'),
            'categories_total' => count($categories),
            'criteria_total' => count($criteria),
            'contestants_total' => count($contestants),
            'judges_total' => count($judges),
            'released_categories' => count(array_filter($categories, static function ($category) {
                return !empty($category->is_released);
            })),
            'pending_progress' => count(array_filter($categories, static function ($category) {
                return (int)($category->progress_percent ?? 0) < 100;
            })),
        ];

        $this->view('admin/dashboard', [
            'events' => $events,
            'categories' => $categories,
            'criteria' => $criteria,
            'contestants' => $contestants,
            'judges' => $judges,
            'assignments' => $assignments,
            'stats' => $stats,
        ]);
    }

    // ---- Event Management ----
    public function events() {
        Auth::requireRole('admin');
        $events = EventModel::getWithCounts();
        $this->view('admin/events', ['events' => $events]);
    }

    public function createEvent() {
        Auth::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectEventData();
            $errors = $this->validateEventData($data);
            if (!empty($errors)) {
                $event = (object)$data;
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderEventForm($event, $errors, false), 422);
                }
                $this->view('admin/event_form', [
                    'event' => $event,
                    'errors' => $errors,
                    'error' => $this->firstError($errors),
                    'action' => '/admin/events/create',
                    'isEdit' => false,
                ]);
                return;
            }

            EventModel::create($data);
            $this->respondMutationSuccess('/admin/events', 'Event created successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderEventForm(null, [], false));
        }

        $this->view('admin/event_form', [
            'event' => null,
            'errors' => [],
            'action' => '/admin/events/create',
            'isEdit' => false,
        ]);
    }

    public function editEvent($id) {
        Auth::requireRole('admin');
        $event = EventModel::getById($id);
        if (!$event) {
            $this->respondNotFound('Event not found.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectEventData();
            $errors = $this->validateEventData($data);
            if (!empty($errors)) {
                $eventError = (object)array_merge((array)$event, $data);
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderEventForm($eventError, $errors, true), 422);
                }
                $this->view('admin/event_form', [
                    'event' => $eventError,
                    'errors' => $errors,
                    'error' => $this->firstError($errors),
                    'action' => '/admin/events/edit/' . $id,
                    'isEdit' => true,
                ]);
                return;
            }

            EventModel::update($id, $data);
            $this->respondMutationSuccess('/admin/events', 'Event updated successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderEventForm($event, [], true));
        }

        $this->view('admin/event_form', [
            'event' => $event,
            'errors' => [],
            'action' => '/admin/events/edit/' . $id,
            'isEdit' => true,
        ]);
    }

    public function deleteEvent($id) {
        Auth::requireRole('admin');
        $this->assertCsrf();
        if (!EventModel::delete($id)) {
            $this->respondMutationError('Unable to delete event.');
        }
        $this->respondMutationSuccess('/admin/events', 'Event deleted successfully.');
    }

    public function duplicateEvent($id) {
        Auth::requireRole('admin');
        $source = EventModel::getById((int)$id);
        if (!$source) $this->respondNotFound('Event not found.');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = ['name' => trim($_POST['name'] ?? ''), 'event_date' => trim($_POST['event_date'] ?? ''), 'description' => trim($_POST['description'] ?? '')];
            if ($data['name'] === '' || $data['event_date'] === '') $this->respondMutationError('Event name and date are required.');
            $newId = EventModel::duplicateStructure((int)$id, $data, (int)Auth::user()->id);
            $this->respondMutationSuccess('/admin/events/manage/' . $newId, 'Event structure duplicated. Contestants, judges, scores, and results were not copied.');
            return;
        }
        $this->view('admin/event_duplicate', ['source' => $source]);
    }

    public function templates() {
        Auth::requireRole('admin');
        $this->view('admin/templates', ['templates' => RubricTemplateModel::getAll()]);
    }

    public function createTemplate() {
        Auth::requireRole('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->view('admin/template_form', []); return; }
        $this->assertCsrf();
        $criteria = $this->templateCriteriaFromPost();
        $data = ['name' => trim($_POST['name'] ?? ''), 'template_category' => trim($_POST['template_category'] ?? ''), 'description' => trim($_POST['description'] ?? ''), 'computation_type' => $_POST['computation_type'] ?? 'weighted_criteria', 'tiebreak_rule' => $_POST['tiebreak_rule'] ?? 'manual', 'criteria' => $criteria];
        if ($data['name'] === '' || empty($criteria)) $this->respondMutationError('A template name and at least one valid criterion are required.');
        if (!$this->validTemplateConfiguration($data, $criteria)) $this->respondMutationError('The template has an invalid scoring configuration or weighted criteria must total exactly 100%.');
        RubricTemplateModel::create($data, (int)Auth::user()->id);
        $this->respondMutationSuccess('/admin/templates', 'Rubric template created successfully.');
    }

    public function deleteTemplate($id) {
        Auth::requireRole('admin'); $this->assertCsrf();
        if (!RubricTemplateModel::delete((int)$id)) $this->respondMutationError('Unable to delete template.');
        $this->respondMutationSuccess('/admin/templates', 'Template deleted successfully.');
    }

    public function exportTemplate($id) {
        Auth::requireRole('admin');
        $template = RubricTemplateModel::export((int)$id);
        if (!$template) $this->respondNotFound('Template not found.');
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rubric-template-' . (int)$id . '.json"');
        echo json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); exit;
    }

    public function importTemplate() {
        Auth::requireRole('admin'); $this->assertCsrf();
        $upload = $_FILES['template_file'] ?? null;
        if (!$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 1048576) $this->respondMutationError('Upload a valid JSON file smaller than 1 MB.');
        $data = json_decode((string)file_get_contents($upload['tmp_name']), true);
        if (!is_array($data) || ($data['format'] ?? '') !== 'ustp-tabulation-rubric/v1') $this->respondMutationError('This is not a compatible rubric-template JSON file.');
        $criteria = $this->templateCriteriaFromArray($data['criteria'] ?? []);
        if (empty($data['name']) || empty($criteria)) $this->respondMutationError('The imported template needs a name and valid criteria.');
        $templateData = ['name'=>trim($data['name']), 'template_category'=>trim($data['category'] ?? ''), 'description'=>trim($data['description'] ?? ''), 'computation_type'=>$data['computation_type'] ?? 'weighted_criteria', 'tiebreak_rule'=>$data['tiebreak_rule'] ?? 'manual', 'criteria'=>$criteria];
        if (!$this->validTemplateConfiguration($templateData, $criteria)) $this->respondMutationError('The imported template has an invalid scoring configuration or weighted criteria must total exactly 100%.');
        RubricTemplateModel::create($templateData, (int)Auth::user()->id);
        $this->respondMutationSuccess('/admin/templates', 'Rubric template imported successfully.');
    }

    public function manageEvent($id) {
        Auth::requireRole('admin');
        $id = (int)$id;
        $event = EventModel::getById($id);
        if (!$event) {
            $this->respondNotFound('Event not found.');
        }

        $categories = CategoryModel::getByEventWithProgress($id);
        $contestants = ContestantModel::getByEvent($id);
        $criteria = CriteriaModel::getByEvent($id);
        $assignments = JudgeAssignmentModel::getByEvent($id);
        $allJudges = UserModel::getAllJudges();

        $this->view('admin/event_workspace', [
            'event' => $event,
            'categories' => $categories,
            'contestants' => $contestants,
            'criteria' => $criteria,
            'assignments' => $assignments,
            'judges' => $allJudges,
        ]);
    }

    // ---- Category Management ----
    public function categories() {
        Auth::requireRole('admin');
        $categories = CategoryModel::getAllWithProgress();
        $this->view('admin/categories', ['categories' => $categories]);
    }

    public function createCategory() {
        Auth::requireRole('admin');
        $workspaceEventId = $this->workspaceEventId();
        $events = $workspaceEventId ? [EventModel::getById($workspaceEventId)] : EventModel::getAll();
        $criteria = CriteriaModel::getAllWithDetails();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectCategoryData();
            $errors = $this->validateCategoryData($data);
            if (!empty($errors)) {
                $category = (object)$data;
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderCategoryForm($category, $errors, false, $events, $criteria, $workspaceEventId), 422);
                }
                $this->view('admin/category_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'events' => $events,
                    'criteria' => $criteria,
                    'category' => $category,
                    'action' => '/admin/categories/create',
                    'isEdit' => false,
                ]);
                return;
            }

            $categoryId = CategoryModel::create($data);
            $templateId = (int)($_POST['rubric_template_id'] ?? 0);
            if ($templateId > 0) {
                RubricTemplateModel::applyToCategory($templateId, (int)$categoryId, (int)Auth::user()->id);
            }
            $this->respondMutationSuccess($this->workspaceRedirect('/admin/categories'), $templateId > 0 ? 'Category and rubric template created successfully.' : 'Category created successfully.');
            return;
        }

        if ($this->isAjax()) {
            $category = $workspaceEventId ? (object)['event_id' => $workspaceEventId] : null;
            $this->respondHtml($this->renderCategoryForm($category, [], false, $events, $criteria, $workspaceEventId));
        }

        $this->view('admin/category_form', [
            'events' => $events,
            'criteria' => $criteria,
            'category' => $workspaceEventId ? (object)['event_id' => $workspaceEventId] : null,
            'errors' => [],
            'action' => '/admin/categories/create',
            'isEdit' => false,
        ]);
    }

    public function editCategory($id) {
        Auth::requireRole('admin');
        $workspaceEventId = $this->workspaceEventId();
        $events = EventModel::getAll();
        $criteria = CriteriaModel::getAllWithDetails();
        $category = CategoryModel::getById($id);
        if (!$category) {
            $this->respondNotFound('Category not found.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectCategoryData();
            $errors = $this->validateCategoryData($data);
            if (!empty($errors)) {
                $categoryError = (object)array_merge((array)$category, $data);
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderCategoryForm($categoryError, $errors, true, $events, $criteria, $workspaceEventId), 422);
                }
                $this->view('admin/category_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'events' => $events,
                    'criteria' => $criteria,
                    'category' => $categoryError,
                    'action' => '/admin/categories/edit/' . $id,
                    'isEdit' => true,
                ]);
                return;
            }

            CategoryModel::update($id, $data);
            $this->respondMutationSuccess($this->workspaceRedirect('/admin/categories'), 'Category updated successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderCategoryForm($category, [], true, $events, $criteria, $workspaceEventId));
        }

        $this->view('admin/category_form', [
            'events' => $events,
            'criteria' => $criteria,
            'category' => $category,
            'errors' => [],
            'action' => '/admin/categories/edit/' . $id,
            'isEdit' => true,
        ]);
    }

    public function deleteCategory($id) {
        Auth::requireRole('admin');
        $this->assertCsrf();
        if (!CategoryModel::delete($id)) {
            $this->respondMutationError('Unable to delete category.');
        }
        $this->respondMutationSuccess($this->workspaceRedirect('/admin/categories'), 'Category deleted successfully.');
    }

    // ---- Criteria Management ----
    public function criteria() {
        Auth::requireRole('admin');
        $criteria = CriteriaModel::getAllWithDetails();
        $this->view('admin/criteria', ['criteria' => $criteria]);
    }

    public function createCriterion() {
        Auth::requireRole('admin');
        $workspaceEventId = $this->workspaceEventId();
        $categories = $workspaceEventId ? CategoryModel::getByEvent($workspaceEventId) : CategoryModel::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectCriterionData();
            $errors = $this->validateCriterionData($data);
            if (!empty($errors)) {
                $criterion = (object)$data;
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderCriterionForm($criterion, $errors, false, $categories, $workspaceEventId), 422);
                }
                $this->view('admin/criterion_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'categories' => $categories,
                    'criterion' => $criterion,
                    'action' => '/admin/criteria/create',
                    'isEdit' => false,
                ]);
                return;
            }

            CriteriaModel::create($data);
            $this->respondMutationSuccess($this->workspaceRedirect('/admin/criteria'), 'Criterion created successfully.');
            return;
        }

        if ($this->isAjax()) {
            $criterion = !empty($_GET['category_id']) ? (object)['category_id' => (int)$_GET['category_id']] : null;
            $this->respondHtml($this->renderCriterionForm($criterion, [], false, $categories, $workspaceEventId));
        }

        $this->view('admin/criterion_form', [
            'categories' => $categories,
            'criterion' => !empty($_GET['category_id']) ? (object)['category_id' => (int)$_GET['category_id']] : null,
            'errors' => [],
            'action' => '/admin/criteria/create',
            'isEdit' => false,
        ]);
    }

    public function editCriterion($id) {
        Auth::requireRole('admin');
        $workspaceEventId = $this->workspaceEventId();
        $categories = CategoryModel::getAll();
        $criterion = CriteriaModel::getById($id);
        if (!$criterion) {
            $this->respondNotFound('Criterion not found.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectCriterionData();
            $errors = $this->validateCriterionData($data, (int)$id);
            if (!empty($errors)) {
                $criterionError = (object)array_merge((array)$criterion, $data);
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderCriterionForm($criterionError, $errors, true, $categories, $workspaceEventId), 422);
                }
                $this->view('admin/criterion_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'categories' => $categories,
                    'criterion' => $criterionError,
                    'action' => '/admin/criteria/edit/' . $id,
                    'isEdit' => true,
                ]);
                return;
            }

            CriteriaModel::update($id, $data);
            $this->respondMutationSuccess($this->workspaceRedirect('/admin/criteria'), 'Criterion updated successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderCriterionForm($criterion, [], true, $categories, $workspaceEventId));
        }

        $this->view('admin/criterion_form', [
            'categories' => $categories,
            'criterion' => $criterion,
            'errors' => [],
            'action' => '/admin/criteria/edit/' . $id,
            'isEdit' => true,
        ]);
    }

    public function deleteCriterion($id) {
        Auth::requireRole('admin');
        $this->assertCsrf();
        if (!CriteriaModel::delete($id)) {
            $this->respondMutationError('Unable to delete criterion.');
        }
        $this->respondMutationSuccess($this->workspaceRedirect('/admin/criteria'), 'Criterion deleted successfully.');
    }

    // ---- Contestant Management ----
    public function contestants() {
        Auth::requireRole('admin');
        $contestants = ContestantModel::getAll();
        $this->view('admin/contestants', ['contestants' => $contestants]);
    }

    public function createContestant() {
        Auth::requireRole('admin');
        $workspaceEventId = $this->workspaceEventId();
        $categories = $workspaceEventId ? CategoryModel::getByEvent($workspaceEventId) : CategoryModel::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectContestantData();
            $errors = $this->validateContestantData($data);
            if (!empty($errors)) {
                $contestant = (object)$data;
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderContestantForm($contestant, $errors, false, $categories, $workspaceEventId), 422);
                }
                $this->view('admin/contestant_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'categories' => $categories,
                    'contestant' => $contestant,
                    'action' => '/admin/contestants/create',
                    'isEdit' => false,
                ]);
                return;
            }

            ContestantModel::create($data);
            $this->respondMutationSuccess($this->workspaceRedirect('/admin/contestants'), 'Contestant created successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderContestantForm(null, [], false, $categories, $workspaceEventId));
        }

        $this->view('admin/contestant_form', [
            'categories' => $categories,
            'contestant' => null,
            'errors' => [],
            'action' => '/admin/contestants/create',
            'isEdit' => false,
        ]);
    }

    public function editContestant($id) {
        Auth::requireRole('admin');
        $workspaceEventId = $this->workspaceEventId();
        $categories = CategoryModel::getAll();
        $contestant = ContestantModel::getById($id);
        if (!$contestant) {
            $this->respondNotFound('Contestant not found.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectContestantData();
            $errors = $this->validateContestantData($data);
            if (!empty($errors)) {
                $contestantError = (object)array_merge((array)$contestant, $data);
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderContestantForm($contestantError, $errors, true, $categories, $workspaceEventId), 422);
                }
                $this->view('admin/contestant_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'categories' => $categories,
                    'contestant' => $contestantError,
                    'action' => '/admin/contestants/edit/' . $id,
                    'isEdit' => true,
                ]);
                return;
            }

            ContestantModel::update($id, $data);
            $this->respondMutationSuccess($this->workspaceRedirect('/admin/contestants'), 'Contestant updated successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderContestantForm($contestant, [], true, $categories, $workspaceEventId));
        }

        $this->view('admin/contestant_form', [
            'categories' => $categories,
            'contestant' => $contestant,
            'errors' => [],
            'action' => '/admin/contestants/edit/' . $id,
            'isEdit' => true,
        ]);
    }

    public function deleteContestant($id) {
        Auth::requireRole('admin');
        $this->assertCsrf();
        if (!ContestantModel::delete($id)) {
            $this->respondMutationError('Unable to delete contestant.');
        }
        $this->respondMutationSuccess($this->workspaceRedirect('/admin/contestants'), 'Contestant deleted successfully.');
    }

    public function bulkCreateContestants() {
        Auth::requireRole('admin');
        $this->assertCsrf();

        $categoryId = (int)($_POST['category_id'] ?? 0);
        $type = $_POST['type'] ?? 'team';
        $preset = $_POST['preset'] ?? '';
        $rawNames = trim($_POST['names'] ?? '');
        $eventId = (int)($_POST['event_id'] ?? 0);

        $category = $categoryId > 0 ? CategoryModel::getById($categoryId) : null;
        if (!$category) {
            $this->respondMutationError('Please select a category.');
            return;
        }
        if ($eventId > 0 && (int)$category->event_id !== $eventId) {
            $this->respondMutationError('The selected category does not belong to this event.');
            return;
        }
        if (!in_array($type, ['solo', 'team'], true)) {
            $this->respondMutationError('Invalid contestant type.');
            return;
        }

        $names = [];
        if ($preset === 'ustp_departments') {
            $names = [
                'BSIT - Information Technology',
                'BSED - Education',
                'BSHM - Hospitality Management',
                'BSBA - Business Administration',
                'BSECE - Computer Engineering',
            ];
        } elseif ($rawNames !== '') {
            $lines = preg_split('/[\r\n]+/', $rawNames);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $names[] = $trimmed;
                }
            }
        }

        if (empty($names)) {
            $this->respondMutationError('No valid contestant names provided.');
            return;
        }

        $inserted = ContestantModel::bulkCreate($categoryId, $names, $type, Auth::user()->id);
        $redirectUrl = $eventId > 0 ? '/admin/events/manage/' . $eventId : '/admin/contestants';

        $this->respondMutationSuccess(
            $redirectUrl,
            "Successfully registered {$inserted} contestant(s)."
        );
    }

    // ---- Judge Assignments ----
    public function judges() {
        Auth::requireRole('admin');
        $judges = UserModel::getAllJudges();
        $categories = CategoryModel::getAll();
        $assignments = JudgeAssignmentModel::getAllWithDetails();
        $this->view('admin/judges', [
            'judges' => $judges,
            'categories' => $categories,
            'assignments' => $assignments,
        ]);
    }

    public function assignJudge() {
        Auth::requireRole('admin');
        $workspaceEventId = $this->workspaceEventId();
        $judges = UserModel::getAllJudges();
        $categories = $workspaceEventId ? CategoryModel::getByEvent($workspaceEventId) : CategoryModel::getAll();
        $assignments = JudgeAssignmentModel::getAllWithDetails();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = [
                'user_id' => (int)($_POST['user_id'] ?? 0),
                'category_id' => (int)($_POST['category_id'] ?? 0),
            ];
            $errors = $this->validateAssignmentData($data);
            if (!empty($errors)) {
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderAssignJudgeForm((object)$data, $errors, $judges, $categories, $workspaceEventId), 422);
                }
                $this->view('admin/assign_judge', [
                    'judges' => $judges,
                    'categories' => $categories,
                    'assignments' => $assignments,
                    'errors' => $errors,
                    'error' => $this->firstError($errors),
                ]);
                return;
            }

            JudgeAssignmentModel::assign($data['user_id'], $data['category_id']);
            $this->respondMutationSuccess($this->workspaceRedirect('/admin/judges'), 'Judge assigned successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderAssignJudgeForm(null, [], $judges, $categories, $workspaceEventId));
        }

        $this->view('admin/assign_judge', [
            'judges' => $judges,
            'categories' => $categories,
            'assignments' => $assignments,
            'errors' => [],
        ]);
    }

    public function removeJudge($assignmentId) {
        Auth::requireRole('admin');
        $this->assertCsrf();
        if (!JudgeAssignmentModel::removeById($assignmentId)) {
            $this->respondMutationError('Unable to remove judge assignment.');
        }
        $this->respondMutationSuccess($this->workspaceRedirect('/admin/judges'), 'Judge assignment removed successfully.');
    }

    // ---- User Management ----
    public function users() {
        Auth::requireRole('admin');
        $users = UserModel::getAll();
        $this->view('admin/users', ['users' => $users]);
    }

    public function createUser() {
        Auth::requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectUserData(false);
            $errors = $this->validateUserData($data, false);
            if (!empty($errors)) {
                $user = (object)$data;
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderUserForm($user, $errors, false), 422);
                }
                $this->view('admin/user_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'user' => $user,
                    'action' => '/admin/users/create',
                    'isEdit' => false,
                ]);
                return;
            }

            UserModel::create($data);
            $this->respondMutationSuccess('/admin/users', 'User created successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderUserForm(null, [], false));
        }

        $this->view('admin/user_form', [
            'user' => null,
            'errors' => [],
            'action' => '/admin/users/create',
            'isEdit' => false,
        ]);
    }

    public function editUser($id) {
        Auth::requireRole('admin');
        $user = UserModel::getById($id);
        if (!$user) {
            $this->respondNotFound('User not found.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $data = $this->collectUserData(true);
            $errors = $this->validateUserData($data, true);
            if (!empty($errors)) {
                $userError = (object)array_merge((array)$user, $data);
                if ($this->isAjax()) {
                    $this->respondHtml($this->renderUserForm($userError, $errors, true), 422);
                }
                $this->view('admin/user_form', [
                    'error' => $this->firstError($errors),
                    'errors' => $errors,
                    'user' => $userError,
                    'action' => '/admin/users/edit/' . $id,
                    'isEdit' => true,
                ]);
                return;
            }

            UserModel::update($id, $data);
            $this->respondMutationSuccess('/admin/users', 'User updated successfully.');
            return;
        }

        if ($this->isAjax()) {
            $this->respondHtml($this->renderUserForm($user, [], true));
        }

        $this->view('admin/user_form', [
            'user' => $user,
            'errors' => [],
            'action' => '/admin/users/edit/' . $id,
            'isEdit' => true,
        ]);
    }

    public function deleteUser($id) {
        Auth::requireRole('admin');
        $this->assertCsrf();
        if (!UserModel::delete($id)) {
            $this->respondMutationError('Unable to delete user.');
        }
        $this->respondMutationSuccess('/admin/users', 'User deleted successfully.');
    }

    // ---- Locked Score Management (FR9) ----
    public function scores() {
        Auth::requireRole('admin');
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        if ($categoryId !== null && $categoryId <= 0) {
            $categoryId = null;
        }

        $this->view('admin/scores', [
            'submissions' => ScoreModel::getLockedSubmissions($categoryId),
            'categories' => CategoryModel::getAll(),
            'selectedCategoryId' => $categoryId,
        ]);
    }

    public function unlockScore($assignmentId, $contestantId) {
        Auth::requireAnyRole(['admin', 'tabulator']);
        $this->assertCsrf();

        $assignmentId = (int)$assignmentId;
        $contestantId = (int)$contestantId;
        if ($assignmentId <= 0 || $contestantId <= 0) {
            $this->respondNotFound('Invalid unlock request.', 400);
        }

        $assignment = JudgeAssignmentModel::getById($assignmentId);
        if (!$assignment) {
            $this->respondNotFound('Assignment not found.');
        }

        ScoreModel::unlockScoresForContestant($assignmentId, $contestantId, Auth::user()->id);
        // Any correction invalidates the previously released public result.
        ResultModel::unreleaseCategory((int)$assignment->category_id);
        CategoryResults::recompute((int)$assignment->category_id, (int)Auth::user()->id);

        $redirect = Auth::user()->role === 'admin' ? '/admin/scores' : '/tabulator/scores';
        if (!empty($_GET['category_id'])) {
            $redirect .= '?category_id=' . (int)$_GET['category_id'];
        }
        $this->redirect($redirect);
    }

    // ---- Helper Methods ----
    private function isAjax(): bool {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private function respondHtml(string $html, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    private function respondMutationSuccess(string $redirect, string $message): void {
        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        $this->redirect($redirect);
    }

    private function respondMutationError(string $message, int $statusCode = 400): void {
        if ($this->isAjax()) {
            $this->json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        }

        http_response_code($statusCode);
        die($message);
    }

    private function respondNotFound(string $message, int $statusCode = 404): void {
        http_response_code($statusCode);
        die($message);
    }

    private function firstError(array $errors): ?string {
        foreach ($errors as $error) {
            if (!empty($error)) {
                return $error;
            }
        }

        return null;
    }

    private function renderEventForm($event = null, array $errors = [], bool $isEdit = false): string {
        return $this->render('admin/event_form_content', [
            'event' => $event,
            'errors' => $errors,
            'isEdit' => $isEdit,
        ]);
    }

    private function renderCategoryForm($category = null, array $errors = [], bool $isEdit = false, array $events = [], array $criteria = [], ?int $returnEventId = null): string {
        return $this->render('admin/category_form_content', [
            'category' => $category,
            'errors' => $errors,
            'isEdit' => $isEdit,
            'events' => $events,
            'criteria' => $criteria,
            'returnEventId' => $returnEventId,
        ]);
    }

    private function renderContestantForm($contestant = null, array $errors = [], bool $isEdit = false, array $categories = [], ?int $returnEventId = null): string {
        return $this->render('admin/contestant_form_content', [
            'contestant' => $contestant,
            'errors' => $errors,
            'isEdit' => $isEdit,
            'categories' => $categories,
            'returnEventId' => $returnEventId,
        ]);
    }

    private function renderCriterionForm($criterion = null, array $errors = [], bool $isEdit = false, array $categories = [], ?int $returnEventId = null): string {
        return $this->render('admin/criterion_form_content', [
            'criterion' => $criterion,
            'errors' => $errors,
            'isEdit' => $isEdit,
            'categories' => $categories,
            'returnEventId' => $returnEventId,
        ]);
    }

    private function renderAssignJudgeForm($assignment = null, array $errors = [], array $judges = [], array $categories = [], ?int $returnEventId = null): string {
        return $this->render('admin/assign_judge_content', [
            'assignment' => $assignment,
            'errors' => $errors,
            'judges' => $judges,
            'categories' => $categories,
            'returnEventId' => $returnEventId,
        ]);
    }

    private function renderUserForm($user = null, array $errors = [], bool $isEdit = false): string {
        return $this->render('admin/user_form_content', [
            'user' => $user,
            'errors' => $errors,
            'isEdit' => $isEdit,
        ]);
    }

    private function collectEventData() {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'event_date' => trim($_POST['event_date'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'created_by' => Auth::user()->id,
        ];
    }

    private function collectCategoryData() {
        return [
            'event_id' => (int)($_POST['event_id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'computation_type' => $_POST['computation_type'] ?? 'raw_average',
            'tiebreak_rule' => $_POST['tiebreak_rule'] ?? 'manual',
            'tiebreak_criterion_id' => !empty($_POST['tiebreak_criterion_id']) ? (int)$_POST['tiebreak_criterion_id'] : null,
            'created_by' => Auth::user()->id,
        ];
    }

    private function collectContestantData() {
        return [
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'solo',
            'created_by' => Auth::user()->id,
        ];
    }

    private function collectCriterionData() {
        return [
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'weight_percent' => (float)($_POST['weight_percent'] ?? 0),
            'min_score' => (float)($_POST['min_score'] ?? 0),
            'max_score' => (float)($_POST['max_score'] ?? 100),
            'created_by' => Auth::user()->id,
        ];
    }

    private function collectUserData(bool $isEdit) {
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => $_POST['role'] ?? 'judge',
        ];

        if (!$isEdit || !empty($_POST['password'])) {
            $data['password'] = $_POST['password'] ?? '';
        }

        return $data;
    }

    private function validateEventData(array $data): array {
        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = 'Event name is required.';
        }
        if ($data['event_date'] === '') {
            $errors['event_date'] = 'Event date is required.';
        }

        return $errors;
    }

    private function validateCategoryData(array $data): array {
        $errors = [];
        if ($data['event_id'] <= 0) {
            $errors['event_id'] = 'Please select an event.';
        }
        if ($data['name'] === '') {
            $errors['name'] = 'Category name is required.';
        }

        return $errors;
    }

    private function validateContestantData(array $data): array {
        $errors = [];
        if ($data['category_id'] <= 0) {
            $errors['category_id'] = 'Please select a category.';
        }
        if ($data['name'] === '') {
            $errors['name'] = 'Contestant name is required.';
        }

        return $errors;
    }

    private function validateCriterionData(array $data, ?int $excludeCriterionId = null): array {
        $errors = [];
        if ($data['category_id'] <= 0) {
            $errors['category_id'] = 'Please select a category.';
        }
        if ($data['name'] === '') {
            $errors['name'] = 'Criterion name is required.';
        }
        if ($data['weight_percent'] < 0 || $data['weight_percent'] > 100) {
            $errors['weight_percent'] = 'Weight percent must be between 0 and 100.';
        }
        if ($data['min_score'] > $data['max_score']) {
            $errors['max_score'] = 'Maximum score must be greater than or equal to minimum score.';
        }

        if (empty($errors)) {
            $existingTotal = CriteriaModel::getTotalWeightPercent((int)$data['category_id'], $excludeCriterionId);
            $projectedTotal = $existingTotal + (float)$data['weight_percent'];
            if ($projectedTotal > 100.0001) {
                $errors['weight_percent'] = 'Total criteria weight for this category cannot exceed 100%.';
            }
        }

        return $errors;
    }

    private function validateAssignmentData(array $data): array {
        $errors = [];
        if ($data['user_id'] <= 0) {
            $errors['user_id'] = 'Please select a judge.';
        }
        if ($data['category_id'] <= 0) {
            $errors['category_id'] = 'Please select a category.';
        }

        return $errors;
    }

    private function validateUserData(array $data, bool $isEdit): array {
        $errors = [];
        if ($data['full_name'] === '') {
            $errors['full_name'] = 'Full name is required.';
        }
        if ($data['email'] === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address.';
        }
        if (!$isEdit && empty($data['password'])) {
            $errors['password'] = 'Password is required.';
        }
        if (!in_array($data['role'] ?? '', ['admin', 'tabulator', 'judge', 'viewer'], true)) {
            $errors['role'] = 'Invalid user role.';
        }

        return $errors;
    }

    private function assertCsrf() {
        if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
            http_response_code(419);
            die('Invalid security token. Please refresh and try again.');
        }
    }

    private function templateCriteriaFromPost(): array {
        return $this->templateCriteriaFromArray(array_map(static function ($name, $index) {
            return ['name' => $name, 'weight_percent' => $_POST['criterion_weight'][$index] ?? null, 'min_score' => $_POST['criterion_min'][$index] ?? null, 'max_score' => $_POST['criterion_max'][$index] ?? null];
        }, $_POST['criterion_name'] ?? [], array_keys($_POST['criterion_name'] ?? [])));
    }

    private function templateCriteriaFromArray(array $rows): array {
        $criteria = [];
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            $weight = (float)($row['weight_percent'] ?? -1);
            $min = (float)($row['min_score'] ?? 0);
            $max = (float)($row['max_score'] ?? 100);
            if ($name !== '' && $weight >= 0 && $weight <= 100 && $min <= $max) $criteria[] = compact('name', 'weight', 'min', 'max') + ['weight_percent' => $weight, 'min_score' => $min, 'max_score' => $max];
        }
        return $criteria;
    }

    private function validTemplateConfiguration(array $data, array $criteria): bool {
        if (!in_array($data['computation_type'] ?? '', ['raw_average', 'rank_based', 'weighted_criteria'], true)
            || !in_array($data['tiebreak_rule'] ?? '', ['highest_criterion', 'sum_criteria', 'more_top_ranks', 'lowest_variance', 'manual'], true)) return false;
        if (($data['computation_type'] ?? '') !== 'weighted_criteria') return true;
        $total = array_sum(array_map(static fn($criterion) => (float)$criterion['weight_percent'], $criteria));
        return abs($total - 100.0) < 0.001;
    }

    private function workspaceEventId(): ?int {
        $eventId = (int)($_POST['return_event_id'] ?? $_GET['event_id'] ?? 0);
        return $eventId > 0 && EventModel::getById($eventId) ? $eventId : null;
    }

    private function workspaceRedirect(string $fallback): string {
        $eventId = $this->workspaceEventId();
        return $eventId ? '/admin/events/manage/' . $eventId : $fallback;
    }
}
