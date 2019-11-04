<?php
namespace Edwin404\Area\Services;

use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\TreeHelper;

class AreaService
{
    /**
     * 列出中国省市树状结构
     * @return array
     */
    public function listChinaProvinceCityTree()
    {
        $provinces = ModelHelper::model('area_china')->select(['areaId', 'parentAreaId', 'sort', 'name'])
            ->where(['parentAreaId' => 0])->get()->toArray();
        $areaIds = array_pluck($provinces, 'areaId');
        $cities = ModelHelper::model('area_china')->select(['areaId', 'parentAreaId', 'sort', 'name'])
            ->whereIn('parentAreaId', $areaIds)->get()->toArray();
        $areas = array_merge($provinces, $cities);
        return TreeHelper::nodeMerge($areas, 0, 'areaId', 'parentAreaId', 'sort', 'asc');
    }

    /**
     * 根据AreaID列出地区信息
     * @param $parentAreaIds
     * @return array
     */
    public function listChinaByParentAreaIds($parentAreaIds)
    {
        if (!is_array($parentAreaIds)) {
            $parentAreaIds = [$parentAreaIds];
        }
        return ModelHelper::model('area_china')->select(['areaId', 'parentAreaId', 'sort', 'name'])
            ->whereIn('parentAreaId', $parentAreaIds)->get()->toArray();
    }

    /**
     * 根据名称列出相关初始化地区信息
     * @param $names array | string = [上海市,上海,长宁区]
     * @return array
     */
    public function listRelatedChinaByName($names)
    {
        if (!is_array($names)) {
            $names = [$names];
        }
        $list = $this->listChinaByParentAreaIds(0);
        if (empty($names)) {
            return $list;
        }

        $relatedList = [];

        $pid = 0;
        $index = 0;
        do {
            $hasMore = false;
            $list = $this->listChinaByParentAreaIds($pid);
            if (empty($list)) {
                break;
            }
            $relatedList = array_merge($relatedList, $list);
            if (isset($names[$index])) {
                foreach ($list as $item) {
                    if ($item['name'] == $names[$index]) {
                        $pid = $item['areaId'];
                        $hasMore = true;
                        break;
                    }
                }
            }
            $index++;
        } while ($hasMore);

        return $relatedList;
    }

    /**
     * 根据AreaID列出所有相关的地址信息
     * @param $areaIds
     * @return array
     */
    public function listChinaAllByAreaIds($areaIds)
    {
        if (!is_array($areaIds)) {
            $areaIds = [$areaIds];
        }
        $areaIdMap = array_fill_keys($areaIds, true);
        $allArea = $this->all();
        $allArea = TreeHelper::nodeMerge($allArea, 0, 'areaId', 'parentAreaId', 'sort', 'asc');
        $areas = [];
        foreach ($allArea as $province) {
            $includeProvince = false;
            if (array_key_exists($province['areaId'], $areaIdMap)) {
                // 包含整个省
                $includeProvince = true;
                foreach ($province['_child'] as $city) {
                    $areas[] = [
                        'areaId' => $city['areaId'],
                        'parentAreaId' => $city['parentAreaId'],
                        'name' => $city['name'],
                        'sort' => $city['sort'],
                    ];
                    foreach ($city['_child'] as $district) {
                        $areas[] = [
                            'areaId' => $district['areaId'],
                            'parentAreaId' => $district['parentAreaId'],
                            'name' => $district['name'],
                            'sort' => $district['sort'],
                        ];
                    }
                }
            } else {
                foreach ($province['_child'] as $city) {
                    $includeCity = false;
                    if (array_key_exists($city['areaId'], $areaIdMap)) {
                        // 包含整个城市
                        $includeProvince = true;
                        $includeCity = true;
                        foreach ($city['_child'] as $district) {
                            $areas[] = [
                                'areaId' => $district['areaId'],
                                'parentAreaId' => $district['parentAreaId'],
                                'name' => $district['name'],
                                'sort' => $district['sort'],
                            ];
                        }
                    } else {
                        foreach ($city['_child'] as $district) {
                            if (array_key_exists($district['areaId'], $areaIdMap)) {
                                $includeCity = true;
                                $areas[] = [
                                    'areaId' => $district['areaId'],
                                    'parentAreaId' => $district['parentAreaId'],
                                    'name' => $district['name'],
                                    'sort' => $district['sort'],
                                ];
                            }
                        }
                    }
                    if ($includeCity) {
                        $areas[] = [
                            'areaId' => $city['areaId'],
                            'parentAreaId' => $city['parentAreaId'],
                            'name' => $city['name'],
                            'sort' => $city['sort'],
                        ];
                    }
                }
            }
            if ($includeProvince) {
                $areas[] = [
                    'areaId' => $province['areaId'],
                    'parentAreaId' => $province['parentAreaId'],
                    'name' => $province['name'],
                    'sort' => $province['sort'],
                ];
            }
        }
        return $areas;
    }

    /**
     * 获取所有地区信息
     * @return mixed
     */
    public function all()
    {
        return ModelHelper::model('area_china')
            ->select(['areaId', 'parentAreaId', 'sort', 'name'])
            ->get()->toArray();
    }

}