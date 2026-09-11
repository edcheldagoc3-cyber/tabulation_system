<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CategoryModel;
use App\Models\EventModel;
use App\Models\ResultModel;

class ViewerController extends Controller {

    public function index() {
        $events = EventModel::getAll();

        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

        if ($eventId <= 0) {
            foreach ($events as $event) {
                $eventCategories = CategoryModel::getByEvent($event->id);
                foreach ($eventCategories as $eventCategory) {
                    if (!empty(ResultModel::getByCategory($eventCategory->id))) {
                        $eventId = (int)$event->id;
                        break 2;
                    }
                }
            }
        }

        if ($eventId <= 0 && !empty($events)) {
            $eventId = (int)$events[0]->id;
        }

        $categories = $eventId > 0 ? CategoryModel::getByEvent($eventId) : [];
        $categoriesWithResults = [];
        foreach ($categories as $category) {
            if (!empty(ResultModel::getByCategory($category->id))) {
                $categoriesWithResults[(int)$category->id] = true;
            }
        }

        $categoryBelongsToEvent = false;
        foreach ($categories as $category) {
            if ((int)$category->id === $categoryId) {
                $categoryBelongsToEvent = true;
                break;
            }
        }

        if (!$categoryBelongsToEvent) {
            $categoryId = 0;
        }

        if ($categoryId <= 0) {
            foreach ($categories as $category) {
                if (isset($categoriesWithResults[(int)$category->id])) {
                    $categoryId = (int)$category->id;
                    break;
                }
            }
            if ($categoryId <= 0 && !empty($categories)) {
                $categoryId = (int)$categories[0]->id;
            }
        }

        // Show computed results immediately; unreleased results are labelled provisional.
        $results = $categoryId > 0 ? ResultModel::getByCategory($categoryId) : [];
        $hasResults = !empty($results);
        $isReleased = $hasResults && count(array_filter($results, static function ($result) {
            return !empty($result->is_released);
        })) === count($results);

        $selectedEvent = null;
        foreach ($events as $event) {
            if ((int)$event->id === $eventId) {
                $selectedEvent = $event;
                break;
            }
        }

        $selectedCategory = null;
        foreach ($categories as $category) {
            if ((int)$category->id === $categoryId) {
                $selectedCategory = $category;
                break;
            }
        }

        $this->view('viewer/leaderboard', [
            'events' => $events,
            'categories' => $categories,
            'selectedEvent' => $selectedEvent,
            'selectedCategory' => $selectedCategory,
            'eventId' => $eventId,
            'categoryId' => $categoryId,
            'results' => $results,
            'hasResults' => $hasResults,
            'isReleased' => $isReleased,
        ]);
    }
}
