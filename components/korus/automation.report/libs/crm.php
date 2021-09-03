<?php
use Bitrix\Crm\EntityRequisite;

class Crm{

    private static $users = [];

    public static function getByBranch(string $branch, string $inn){
        // UF_CRM_C467A87BE9
        $companyId = self::getByInn($inn);
        if(!empty($companyId)){
            $branch = CIblockElement::GetList([], ['IBLOCK_ID' => \Bitrix\Main\Config\Option::get('onizamov.crmexchange', 'FILIAL_IBLOCK_ID'), 'NAME' => $branch])->Fetch();
            $company = \CCrmCompany::GetList([], ["ID" => $companyId, "UF_CRM_C467A87BE9"=> $branch['ID']])->fetch();
            if($company){
                return $company['ID'];
            }
        }

		return null;
    }

    public static function getByInn(string $inn){
        $result = \Bitrix\Crm\CompanyTable::getList([
            'filter' => [
                'requisit.RQ_INN' => trim($inn),
                'UF_CRM_BCC24F94D4' => 104
            ],
            'select' => ['ID'],
            'runtime' => [
                new \Bitrix\Main\Entity\ReferenceField(
                    'requisit',
                    \Bitrix\Crm\RequisiteTable::class,
                    [
                        '=this.ID' => 'ref.ENTITY_ID',
                        \CCrmOwnerType::Company => 'ref.ENTITY_TYPE_ID',
                    ]
                )
            ]
        ]);

//        $requesite = new EntityRequisite();
//        $result = $requesite->getList([
//            'filter' => ['ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'RQ_INN' => trim($inn)]]);
        $ids = [];
        while ($company = $result->fetch()) {
            $ids[] = $company['ID'];
        }
        return $ids;
    }
    
    public static function getUserByBinding(array $binding){
        if($binding[0]['OWNER_TYPE_ID'] == \CCrmOwnerType::Lead){
            $entity = \CCrmLead::GetByID($binding[0]['OWNER_ID']);
        }

        if($binding[0]['OWNER_TYPE_ID'] == \CCrmOwnerType::Company){
            $entity = \CCrmCompany::GetByID($binding[0]['OWNER_ID']);
        }

        if($entity){
            return $entity['ASSIGNED_BY_ID'];
        }
    } 

    public static function getUsers(string $users){
        $userList = explode(';', $users);

        $return = [];
        foreach ($userList as $user) {
            $return[] = self::getUser($user);
        }
        return $return;
    }

    public static function getUser(string $user){
        if(isset(self::$users[$user])){
            return self::$users[$user];
        }

		$arUser = explode(' ', $user);

        $filter = ['LAST_NAME' => $arUser[0], 'NAME' => $arUser[1], 'SECOND_NAME' =>  $arUser[2]];
        $assigned = \CUser::GetList(($by = "LAST_NAME"), ($order = "asc"), $filter)->Fetch();
        if ($assigned) {
            self::$users[$user] = $assigned['ID'];
            return $assigned['ID'];
        }
    }
    
    public static function getLead(string $title){
        $lead = \CCrmLead::GetList([], ["TITLE" => $title])->fetch();
        if($lead){
            return $lead['ID'];
        }
    }

    public static function getCompany(string $title){
        $company = \CCrmCompany::GetList([], ["TITLE" => $title, 'UF_CRM_BCC24F94D4' => 104])->fetch();
        if($company){
            return $company['ID'];
        }
    }
}