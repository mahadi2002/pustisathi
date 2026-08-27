<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Response;

/**
 * Free-tier food lookup: name + macros only, capped per day for guests.
 * A logged-in user (any role) searches without limit — the cap exists to
 * keep the free surface useful for SEO/trust without giving away the whole
 * database. Answers either a full page (normal navigation) or JSON (the
 * live-search JS on the page fetches this same URL with an
 * Accept: application/json header instead of reloading).
 */
final class FoodController extends Controller
{
    public function search(Request $request): Response
    {
        $query    = trim($request->str('q'));
        $results  = [];
        $capped   = false;
        $unlocked = $this->isAuthenticated();

        if ($query !== '') {
            if (!$unlocked) {
                $limit = (int) config('app.foods.search_daily_cap_guest', 10);
                $retry = RateLimit::hit('foods_search', 'ip:' . $request->ipHash(), $limit, 86400);
                $capped = $retry !== null;
            }

            if (!$capped) {
                $like    = '%' . $query . '%';
                $results = Db::all(
                    'SELECT name_bn, name_en, category, per_100g_kcal, per_100g_protein_g, per_100g_carb_g, per_100g_fat_g
                     FROM food_items WHERE name_bn LIKE ? OR name_en LIKE ? ORDER BY name_bn LIMIT 20',
                    [$like, $like]
                );
            }
        }

        if ($request->wantsJson()) {
            return $this->json([
                'query'   => $query,
                'results' => $results,
                'capped'  => $capped,
            ]);
        }

        return $this->view('public/foods', [
            'query'   => $query,
            'results' => $results,
            'capped'  => $capped,
        ]);
    }
}
