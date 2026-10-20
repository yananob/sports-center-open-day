<?php

declare(strict_types=1);

namespace App\Repositories;

use App\AppConfig;
use App\Models\OpenDaySchedule;
use App\Models\TargetCenter;
use Google\Cloud\Firestore\FirestoreClient;

/**
 * Firestoreデータベースに対するデータ操作を行うリポジトリ。
 */
class FirestoreRepository
{
    private FirestoreClient $db;
    private string $rootCollection;

    public function __construct(?FirestoreClient $db = null)
    {
        // PHP 8.4のCloud Run Functions環境でgRPC通信時に無限再帰エラーが発生する問題を回避するため、REST transportを明示指定します。
        $this->db = $db ?? new FirestoreClient([
            'transport' => 'rest',
        ]);
        $this->rootCollection = AppConfig::getFirestoreRootCollection();
    }

    /**
     * 収集対象のスポーツセンターリストを保存します。
     *
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
     * 収集対象のスポーツセンターリストを取得します。
     *
     * @return TargetCenter[]
     */
    public function getTargets(): array
    {
        $collection = $this->db->collection($this->rootCollection)->document('data')->collection('targets');
        $documents = $collection->documents();

        $targets = [];
        foreach ($documents as $doc) {
            $data = $doc->data();
            $targets[] = TargetCenter::fromArray($doc->id(), $data);
        }
        return $targets;
    }

    /**
     * 特定スポーツセンターのスケジュール情報を保存します。
     *
     * @param string $centerId
     * @param OpenDaySchedule[] $schedules
     */
    public function saveSchedules(string $centerId, array $schedules): void
    {
        $writer = $this->db->bulkWriter();
        $collection = $this->db->collection($this->rootCollection)->document('data')->collection('schedules');

        // 該当センターの既存スケジュールを削除
        $existing = $collection->where('centerId', '=', $centerId)->documents();
        foreach ($existing as $doc) {
            $writer->delete($doc->reference());
        }

        // 新規スケジュールを追加
        foreach ($schedules as $schedule) {
            $newDoc = $collection->newDocument();
            $writer->set($newDoc, $schedule->toArray());
        }

        $writer->close();
    }

    /**
     * 全てのスケジュール情報を取得します。
     *
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
