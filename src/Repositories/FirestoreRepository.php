<?php

declare(strict_types=1);

namespace App\Repositories;

use App\AppConfig;
use App\Models\OpenDaySchedule;
use App\Models\TargetCenter;
use Google\Cloud\Firestore\FirestoreClient;

class FirestoreRepository
{
    private FirestoreClient $db;
    private string $rootCollection;

    public function __construct()
    {
        $config = json_decode(getenv("FIREBASE_SERVICE_ACCOUNT") ?: '{}', true);
        $this->db = new FirestoreClient([
            "keyFile" => $config
        ]);
        $this->rootCollection = AppConfig::getFirestoreRootCollection();
    }

    /**
     * @param TargetCenter[] $targets
     */
    public function saveTargets(array $targets): void
    {
        $writer = $this->db->bulkWriter();
        $collection = $this->db->collection($this->rootCollection)->document('data')->collection('targets');

        // IDでユニーク化
        $newTargets = [];
        foreach ($targets as $target) {
            $newTargets[$target->id] = $target;
        }

        // 既存のターゲットを取得
        $existing = $collection->documents();
        foreach ($existing as $doc) {
            // 新しいリストに含まれない場合のみ削除
            if (!isset($newTargets[$doc->id()])) {
                $writer->delete($doc->reference());
            }
        }

        // 新しいターゲットを保存（上書き）
        foreach ($newTargets as $target) {
            $docRef = $collection->document($target->id);
            $writer->set($docRef, [
                'name' => $target->name,
                'url' => $target->url,
            ]);
        }

        $writer->close();
    }

    /**
     * @return TargetCenter[]
     */
    public function getTargets(): array
    {
        $collection = $this->db->collection($this->rootCollection)->document('data')->collection('targets');
        $documents = $collection->documents();

        $targets = [];
        foreach ($documents as $doc) {
            $targets[] = TargetCenter::fromArray($doc->id(), $doc->data());
        }
        return $targets;
    }

    /**
     * @param OpenDaySchedule[] $schedules
     */
    public function saveSchedules(string $centerId, array $schedules): void
    {
        $writer = $this->db->bulkWriter();
        $collection = $this->db->collection($this->rootCollection)->document('data')->collection('schedules');

        // Delete existing schedules for this center
        $existing = $collection->where('centerId', '=', $centerId)->documents();
        foreach ($existing as $doc) {
            $writer->delete($doc->reference());
        }

        // Add new schedules
        foreach ($schedules as $schedule) {
            $newDoc = $collection->newDocument();
            $writer->set($newDoc, $schedule->toArray());
        }

        $writer->close();
    }

    /**
     * @return OpenDaySchedule[]
     */
    public function getAllSchedules(): array
    {
        $collection = $this->db->collection($this->rootCollection)->document('data')->collection('schedules');
        $documents = $collection->orderBy('date', 'ASC')->documents();

        $schedules = [];
        foreach ($documents as $doc) {
            $schedules[] = OpenDaySchedule::fromArray($doc->data());
        }
        return $schedules;
    }
}
