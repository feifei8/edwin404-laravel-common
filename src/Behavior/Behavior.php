<?php

namespace Edwin404\Behavior;


use Edwin404\Base\Support\ModelHelper;
use Edwin404\Behavior\Types\BehaviorPeriod;
use Illuminate\Support\Facades\DB;

class Behavior
{
    /**
     * 主要功能是关键点的防刷
     * 记录行为并返回用户在PERIOD时间段该行为的次数
     * 该操作是原子的
     *
     *
     * @param $action string 行为字符串
     * @param $period integer 周期,请使用 BehaviorPeriod
     *
     * @return integer 周期内行为次数
     */
    public static function hits($action, $period)
    {
        ModelHelper::transactionBegin();
        $behavior = ModelHelper::loadWithLock('behavior', ['action' => $action, 'period' => $period]);
        if (empty($behavior)) {
            $where['hits'] = 1;
            $behavior = ModelHelper::add('behavior', ['action' => $action, 'period' => $period, 'hits' => 1]);
        } else {
            switch ($period) {
                case BehaviorPeriod::ONE_MINUTE:
                    $time = time() - 60;
                    break;
                case BehaviorPeriod::FIVE_MINUTES:
                    $time = time() - 5 * 60;
                    break;
                case BehaviorPeriod::TEN_MINUTES:
                    $time = time() - 10 * 60;
                    break;
                case BehaviorPeriod::THIRTY_MINUTES:
                    $time = time() - 30 * 60;
                    break;
                case BehaviorPeriod::HOUR:
                    $time = time() - 60 * 60;
                    break;
                case BehaviorPeriod::DAY:
                    $time = time() - 3600 * 24;
                    break;
                default:
                    $time = time() - 3600 * 24;
                    break;
            }
            $hits = intval(DB::table('behavior_log')->where('time', '>', $time)->where(['action' => $action])->count());
            $behavior = ModelHelper::updateOne('behavior', ['id' => $behavior['id']], ['hits' => $hits]);
        }
        ModelHelper::transactionCommit();
        return intval($behavior['hits']);
    }

    /**
     * 记录行为
     *
     * @param $action
     */
    public static function log($action)
    {
        ModelHelper::add('behavior_log', ['time' => time(), 'action' => $action,]);
    }
}