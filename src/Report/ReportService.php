<?php

namespace Edwin404\Report;


use Edwin404\Base\Support\ModelHelper;

class ReportService
{
    public function countDaily($tableName, $tableWhere, $fromDay, $toDay)
    {
        $startTimestamp = strtotime($fromDay);
        $toTimestamp = strtotime($toDay);
        $reports = [];
        for ($timestamp = $startTimestamp; $timestamp <= $toTimestamp; $timestamp += 24 * 3600) {
            $reports[date('Y-m-d', $timestamp)] = null;
        }
        $counts = ModelHelper::model('report_count_daily')
            ->where(['tableName' => $tableName, 'tableWhere' => json_encode($tableWhere)])
            ->where('day', '>=', $fromDay)
            ->where('day', '<=', $toDay)
            ->get()->toArray();
        foreach ($counts as $count) {
            $reports[date('Y-m-d', strtotime($count['day']))] = $count['cnt'];
        }
        foreach ($reports as $reportDay => $reportCount) {
            if (null === $reportCount) {
                $reports[$reportDay] = $this->countDayFromTable($tableName, $tableWhere, $reportDay);
            }
        }
        return [
            'time' => array_keys($reports),
            'value' => array_values($reports),
        ];
    }

    private function countDayFromTable($tableName, $tableWhere, $day)
    {
        $count = ModelHelper::model($tableName)
            ->where($tableWhere)
            ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($day)))
            ->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($day)))
            ->count();
        if (strtotime($day) < strtotime(date('Y-m-d', time()))) {
            ModelHelper::add('report_count_daily', [
                'tableName' => $tableName,
                'tableWhere' => json_encode($tableWhere),
                'day' => $day,
                'cnt' => $count,
            ]);
        }
        return $count;
    }
}