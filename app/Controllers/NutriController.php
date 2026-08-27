<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Reached only once RequireNutritionist has already confirmed approval.
 *
 * This is the minimal real version of the patient<->nutritionist feature,
 * not a mock: `nutritionist_patients` and `clinical_notes` (migration 008)
 * had zero UI anywhere before this — a patient hands over the short code
 * from their dashboard (DashboardController::shareCode), the nutritionist
 * enters it here to create the link, and clinical_notes gets a plain
 * threaded form + reverse-chronological list per patient. No real-time
 * chat, no read receipts, no note-editing — intentionally simple for a
 * hobby project.
 */
final class NutriController extends Controller
{
    public function home(Request $request): Response
    {
        $nutritionistId = $this->currentUserId();

        $patientCount = (int) Db::value(
            "SELECT COUNT(*) FROM nutritionist_patients WHERE nutritionist_id = ? AND status = 'active'",
            [$nutritionistId]
        );

        // "Pending" here means a linked patient this nutritionist has never
        // written a single clinical note for yet — there's no status column
        // on clinical_notes (a plain append-only log, by design), so
        // "owes an initial note" is the honest reading of "pending" without
        // inventing a workflow state nothing else in the schema supports.
        $pendingNotes = (int) Db::value(
            "SELECT COUNT(*) FROM nutritionist_patients np
             WHERE np.nutritionist_id = ? AND np.status = 'active'
               AND NOT EXISTS (
                   SELECT 1 FROM clinical_notes cn
                   WHERE cn.nutritionist_id = np.nutritionist_id AND cn.patient_id = np.patient_id
               )",
            [$nutritionistId]
        );

        $roster = Db::all(
            "SELECT u.id, u.email, np.linked_at,
                    (SELECT MAX(s.last_active) FROM sessions s WHERE s.user_id = u.id) AS last_active,
                    (SELECT COUNT(*) FROM clinical_notes cn WHERE cn.nutritionist_id = np.nutritionist_id AND cn.patient_id = np.patient_id) AS notes_count
             FROM nutritionist_patients np
             JOIN users u ON u.id = np.patient_id
             WHERE np.nutritionist_id = ? AND np.status = 'active'
             ORDER BY np.linked_at DESC",
            [$nutritionistId]
        );

        return $this->view('nutri/home', [
            'patientCount' => $patientCount,
            'pendingNotes' => $pendingNotes,
            'roster'       => $roster,
        ]);
    }

    /** Patient hands over their share code; this is what turns it into a link row. */
    public function link(Request $request): Response
    {
        $nutritionistId = $this->currentUserId();

        $v = Validator::make($request->body, [
            'code' => 'required|min:8|max:8',
        ], ['code' => 'কোড']);

        if ($v->fails()) {
            Session::notify('error', $v->firstError() ?? 'সঠিক কোড দিন।');
            return $this->redirect('/nutri');
        }

        $code      = strtoupper($v->get('code'));
        $patientId = Db::value('SELECT user_id FROM body_profiles WHERE share_code = ?', [$code]);

        if ($patientId === null) {
            Session::notify('error', 'এই কোডে কোনো Patient খুঁজে পাওয়া যায়নি। কোডটি আবার যাচাই করুন।');
            return $this->redirect('/nutri');
        }

        if ((int) $patientId === $nutritionistId) {
            Session::notify('error', 'নিজের কোড দিয়ে যুক্ত হওয়া যাবে না।');
            return $this->redirect('/nutri');
        }

        Db::exec(
            "INSERT INTO nutritionist_patients (nutritionist_id, patient_id, status)
             VALUES (?, ?, 'active')
             ON DUPLICATE KEY UPDATE status = 'active'",
            [$nutritionistId, $patientId]
        );

        Session::notify('success', 'Patient সফলভাবে যুক্ত হয়েছে।');
        return $this->redirect('/nutri');
    }

    /** Per-patient clinical notes thread — plain form + reverse-chronological list. */
    public function patient(Request $request, string $id): Response
    {
        $nutritionistId = $this->currentUserId();
        $patientId      = (int) $id;

        $link = Db::first(
            "SELECT linked_at FROM nutritionist_patients WHERE nutritionist_id = ? AND patient_id = ? AND status = 'active'",
            [$nutritionistId, $patientId]
        );

        if ($link === null) {
            $this->notFound();
        }

        $patient = Db::first('SELECT id, email FROM users WHERE id = ? AND deleted_at IS NULL', [$patientId]);
        if ($patient === null) {
            $this->notFound();
        }

        $profile = Db::first('SELECT * FROM body_profiles WHERE user_id = ?', [$patientId]);

        $notes = Db::all(
            'SELECT * FROM clinical_notes WHERE nutritionist_id = ? AND patient_id = ? ORDER BY created_at DESC',
            [$nutritionistId, $patientId]
        );

        return $this->view('nutri/patient', [
            'patient'  => $patient,
            'profile'  => $profile,
            'linkedAt' => $link['linked_at'],
            'notes'    => $notes,
        ]);
    }

    public function addNote(Request $request, string $id): Response
    {
        $nutritionistId = $this->currentUserId();
        $patientId      = (int) $id;

        $link = Db::value(
            "SELECT 1 FROM nutritionist_patients WHERE nutritionist_id = ? AND patient_id = ? AND status = 'active'",
            [$nutritionistId, $patientId]
        );

        if ($link === null) {
            $this->notFound();
        }

        $v = Validator::make($request->body, [
            'note' => 'required|min:1|max:2000',
        ], ['note' => 'নোট']);

        if ($v->fails()) {
            Session::notify('error', $v->firstError() ?? 'নোট লিখুন।');
            return $this->redirect('/nutri/patients/' . $patientId);
        }

        Db::insert(
            'INSERT INTO clinical_notes (nutritionist_id, patient_id, note) VALUES (?, ?, ?)',
            [$nutritionistId, $patientId, $v->get('note')]
        );

        Session::notify('success', 'নোট যোগ করা হয়েছে।');
        return $this->redirect('/nutri/patients/' . $patientId);
    }
}
