<?php

namespace App\Controllers;

use App\Models\DuplicateReviewGroupModel;
use App\Models\DuplicateReviewMemberModel;
use App\Models\TeacherModel;
use App\Services\TeacherMergeService;

class DuplicateReviewController extends BaseController
{
    public function index()
    {
        if (!has_permission('duplicates.view')) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $groupModel = new DuplicateReviewGroupModel();
        $status = $this->request->getGet('status') ?? 'OPEN';

        $builder = $groupModel;
        if ($status !== 'ALL') {
            $builder->where('status', $status);
        }

        $groups = $builder->orderBy('confidence_score', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(20);

        return view('duplicates/index', [
            'title'             => 'Review Duplikasi Master Data',
            'breadcrumb_active' => 'Review Duplikasi',
            'groups'            => $groups,
            'pager'             => $groupModel->pager,
            'selectedStatus'    => $status,
        ]);
    }

    public function show(string $uuid)
    {
        if (!has_permission('duplicates.view')) {
            return redirect()->to('/duplicates')->with('error', 'Anda tidak memiliki hak akses.');
        }

        $groupModel = new DuplicateReviewGroupModel();
        $group = $groupModel->where('uuid', $uuid)->first();

        if (!$group) {
            return redirect()->to('/duplicates')->with('error', 'Group review duplikasi tidak ditemukan.');
        }

        $memberModel = new DuplicateReviewMemberModel();
        $members = $memberModel->where('group_id', $group['id'])->findAll();

        $teacherModel = new TeacherModel();
        $teacherEntities = [];
        foreach ($members as $m) {
            if (!empty($m['entity_id'])) {
                $t = $teacherModel->find($m['entity_id']);
                if ($t) {
                    $teacherEntities[] = $t;
                }
            }
        }

        return view('duplicates/show', [
            'title'             => 'Side-by-Side Review Duplikasi',
            'breadcrumb_active' => 'Review Detail',
            'group'             => $group,
            'members'           => $members,
            'teacherEntities'   => $teacherEntities,
        ]);
    }

    public function resolve(string $uuid)
    {
        if (!has_permission('duplicates.resolve')) {
            return redirect()->to('/duplicates')->with('error', 'Anda tidak memiliki hak akses resolusi.');
        }

        $decision = $this->request->getPost('decision');
        $groupModel = new DuplicateReviewGroupModel();
        $group = $groupModel->where('uuid', $uuid)->first();

        if (!$group) {
            return redirect()->to('/duplicates')->with('error', 'Group review tidak ditemukan.');
        }

        try {
            if ($decision === 'MERGE') {
                $canonicalId = (int)$this->request->getPost('canonical_teacher_id');
                $duplicateId = (int)$this->request->getPost('duplicate_teacher_id');
                $mergedData  = (array)$this->request->getPost('fields');
                $reason      = $this->request->getPost('reason');

                TeacherMergeService::merge($canonicalId, $duplicateId, $mergedData, $group['id'], $reason);
                return redirect()->to('/duplicates')->with('success', 'Data guru berhasil di-merge.');
            }

            if ($decision === 'KEEP_SEPARATE') {
                $groupModel->update($group['id'], [
                    'status'          => 'RESOLVED',
                    'decision'        => 'KEEP_SEPARATE',
                    'decision_reason' => $this->request->getPost('reason') ?? 'Diputuskan sebagai data terpisah',
                    'reviewed_by'     => session()->get('user_id'),
                    'reviewed_at'     => date('Y-m-d H:i:s'),
                ]);
                return redirect()->to('/duplicates')->with('success', 'Status ditetapkan sebagai data terpisah.');
            }

            return redirect()->back()->with('error', 'Keputusan tidak dikenal.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
