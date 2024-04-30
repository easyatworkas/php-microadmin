<?php
/**
 * MicroAdmin-Tool
 * TODO: Error handling and logging
 * @author Torbjørn Kallstad
 */

use Ext\Models\Customer;
use Ext\Models\CustomerBusinessUnit;
use Ext\Models\CustomerChecklist;
use Ext\Models\CustomerDefaultHrFile;
use Ext\Models\CustomerFileType;
use Ext\Models\CustomerGroup;
use Ext\Models\CustomerKpiType;
use Ext\Models\CustomerLink;
use Ext\Models\CustomerPayTypeLink;
use Ext\Models\CustomerPosition;
use Ext\Models\CustomerQualification;
use Ext\Models\CustomerReport;
use Ext\Models\CustomerRole;
use Ext\Models\CustomerTodoStatus;
use Ext\Models\CustomerUserGroup;
use Ext\Models\Product;
use Ext\Models\Property;
use Ext\Models\SettingGroup;
use Ext\Models\SettingGroupCustomField;
use Ext\Models\User;

// Configuration
$stackId = 1;
$timeZone = 'Europe/Oslo';
$currency = 'NOK';
$countryCode = 'NO';
$languageCode = 'no';
$localeCode = 'nb-NO';
$maxGroupNameLength = 64;

// If you add more options here, make sure to add them to the validOptions function
$copyOptions = [
    ['id' => 'products', 'name' => 'Products/Modules'],
    ['id' => 'customer_groups', 'name' => 'Customer Groups'],
    ['id' => 'kpi_types', 'name' => 'KPI Types'],
    ['id' => 'pay_type_links', 'name' => 'Pay Types'],
    ['id' => 'hr_file_types', 'name' => 'HR File Types'],
    ['id' => 'reports', 'name' => 'Reports'],
    ['id' => 'business_units', 'name' => 'Business Units'],
    ['id' => 'checklists', 'name' => 'Checklist Templates'],
    ['id' => 'todo_statuses', 'name' => 'Todo Statuses'],
    ['id' => 'user_groups', 'name' => 'User Groups/Permissions'],
    ['id' => 'positions', 'name' => 'Positions'],
    ['id' => 'qualifications', 'name' => 'Qualifications'],
    ['id' => 'default_hr_files', 'name' => 'Default HR Files'],
    ['id' => 'logo', 'name' => 'Logo'],
    ['id' => 'properties', 'name' => 'Settings'],
//    ['id' => 'links', 'name' => 'Relations'],
    ['id' => 'setting_group', 'name' => 'Organizational Settings'],
];

// Initialization
$first = true;
$indent = 0;
$commandLine = false;

// Create Log file
date_default_timezone_set($timeZone);
$logFile = basename(__FILE__, '.php') . ' [' . date('Y-m-d H.i.s') . '].log';
putenv('EAW_LOG_FILE_PATH=' . $logFile);

// Start script
startScript('easy@work MicroAdmin-Tool', $logFile, 'Azets: ');

// =====================================================================================================================

do {
    $mode = menu('Main Menu:', [
        1 => "View Organizational Structure",                           // Done
        2 => "Create new Organization",                                 // TODO: On hold (1 item left)
        3 => "Add new Entity to existing Organization",                 // Done?
        4 => "Copy from one Entity to another",                         // Done
        5 => "Other",                                                   // TODO: In progress...
        'x' => "Exit"                                                   // Done
    ], '#', $indent, $first);

    $first = false;

    switch ($mode) {
        case '1': // View Organizational Structure
            $indent++;
            if (true) {
                inform($indent, 'View Organizational Structure (Setting Group):', true, true, true, '─» ');

                // Select Chain to View
                $input = select($indent, '', modelList(SettingGroup::getAll()->all(), 'name', 'id'), null, true, '#', false, true, false, false, false, true);

                if ($input !== 'exit') {
                    $settingGroup = SettingGroup::get($input);

                    // Title
                    $indent++;
                    if (true) {
                        $org = "$settingGroup->id: $settingGroup->name:";
                        inform($indent, $org, true, false, true, '─« ', true);
                        warn($indent, $GLOBALS['input_colors'][$indent] . '  ╒' . str_repeat('═', mb_strlen($org) + 1), false, false, false, '', true);

                        // Get Chain Structure
                        $chainStructure = newGetChainStructure($settingGroup->id);

                        // View Chain Structure
                        newViewChainStructure($chainStructure, $indent);
                    }
                    $indent--;
                }

                // Done
                feedback($indent, "Done", false, true, '─« ');

                // Go back to Main Menu
            }
            $indent--;

            break;

        case '2': // Create new Organization
            $indent++;
            if (true) {
                // Step 1: Setting Group
                do {
                    $choice = menu('Create new Organization', [
                        1 => "Clone from Template",                 // Done
                        2 => "Create from Scratch",                 // TODO: On hold (1 item left)
                        'x' => "Back to Main Menu"                  // Done
                    ], '#', $indent, false);

                    switch ($choice) {
                        case '1': // Clone from Template
                            $indent++;
                            if (true) {
                                // Get Name of New Setting Group
                                $name = null;
                                do {
                                    $name = choice($indent, 'Name of new Organization:', '@', true);

                                    // Check if name length is valid, or already taken
                                    if (mb_strlen($name) > $maxGroupNameLength)
                                    {
                                        warn($indent, "! Name too long, max $maxGroupNameLength characters");
                                        $name = null;
                                    }
                                    else
                                    {
                                        foreach (SettingGroup::filter($name)->getAll()->all() as $found) {
                                            if ($found->name === $name)
                                            {
                                                warn($indent, "! Setting Group with that name already exists");
                                                $name = null;
                                                break;
                                            }
                                        }
                                    }
                                } while (is_null($name));

                                // Get Name of Billing Contact
                                $contact = choice($indent, 'Name of Billing Contact:', '@', true, false);

                                // Select Template Setting Group
                                inform($indent, 'Select Template Setting Group:', true, true, true, '─» ');
                                $template = SettingGroup::get(
                                    select($indent, '', modelList(SettingGroup::getAll()->all(), 'name', 'id'), null, true, '#', false, true, false, false)
                                );

                                // Verify selection
                                inform($indent, "Selected $template->id: $template->name", true, true, true, '─» ');

                                // Create New Setting Group
                                $newSettingGroup = SettingGroup::newInstance([
                                    'name' => $name,
                                ]);
                                $newSettingGroup->save();

                                // Store Billing Contact as Property
                                $newSettingGroup->addProperty('billing_contact', $contact);

                                // Clone Template Setting Group
                                cloneSettingGroup($indent, $newSettingGroup, $template);

                                // Done
                                // feedback($indent, "$newSettingGroup->name Created", false, true, '─« ');

                                // Go back to Main Menu
                            }
                            $indent--;

                            break;

                        case '2': // Create from Scratch
                            $indent++;
                            if (true) {
                                // Get Name of New Setting Group
                                $name = null;
                                do {
                                    $name = choice($indent, 'Name of new Organization:', '@', true);

                                    // Check if name length is valid, or already taken
                                    if (mb_strlen($name) > $maxGroupNameLength)
                                    {
                                        warn($indent, "! Name too long, max $maxGroupNameLength characters");
                                        $name = null;
                                    }
                                    else
                                    {
                                        foreach (SettingGroup::filter($name)->getAll()->all() as $found) {
                                            if ($found->name === $name)
                                            {
                                                warn($indent, "! Setting Group with that name already exists");
                                                $name = null;
                                                break;
                                            }
                                        }
                                    }
                                } while (is_null($name));

                                // Get Name of Billing Contact
                                $contact = choice($indent, 'Name of Billing Contact:', '@', true, false);

                                // Create New Setting Group
                                $newSettingGroup = SettingGroup::newInstance([
                                    'name' => $name,
                                ]);
                                $newSettingGroup->save();

                                // Store Billing Contact as Property
                                $newSettingGroup->addProperty('billing_contact', $contact);

                                // TODO: Menu: Add Custom Fields, Tariffs, Absence Types, Contract Types, Observers, Properties, etc.
                                warn($indent, "! Add Custom Fields, Tariffs, Absence Types, Contract Types, Observers and Properties manually");

                                // Done
                                feedback($indent, "$newSettingGroup->name Created", false, true, '─« ');

                                // Go back to Main Menu
                            }
                            $indent--;

                            break;

                        case 'x':
                            inform($indent, 'Returning to Main Menu', true, false, true);
                            break;
                    }
                } while ($choice !== 'x');

                // Go back to Main Menu
            }
            $indent--;

            break;

        case '3': // Add new Entity to existing Organization
            $indent++;
            if (true) {
                $allSettingGroups = SettingGroup::getAll()->all();

                // Select Template Setting Group
                inform($indent, 'Select Organization to modify:', true, true, true, '─» ');
                $input = select($indent, '', modelList($allSettingGroups, 'name', 'id'), null, true, '#', false, true, false, false, false, true);

                if ($input !== 'exit') {
                    $settingGroup = SettingGroup::get($input);

                    // View Organizational Structure
                    $indent++;
                    if (true) {
                        $org = "$settingGroup->id: $settingGroup->name:";
                        inform($indent, $org, true, false, true, '─« ', true);
                        warn($indent, $GLOBALS['input_colors'][$indent] . '  ╒' . str_repeat('═', mb_strlen($org) + 1), false, false, false, '', true);

                        // Get Chain Structure
                        $chainStructure = newGetChainStructure($settingGroup->id);

                        // View Chain Structure
                        newViewChainStructure($chainStructure, $indent);
                    }
                    $indent--;

                    // Select Entity Type
                    $customerTypes = eaw()->read('/customer_types');
                    unset($customerTypes['EASYATWORK']);
                    unset($customerTypes['PARTNER']);
                    $customerTypes = array_keys($customerTypes);
                    inform($indent, 'Select Entity Type to add to [' . $settingGroup->name . ']:', true, true, true, '─» ');
                    $type = select($indent, 'Type:', array_combine($customerTypes, $customerTypes), null, true,
                        '#', false, true, false, false, false, true
                    );

                    // Allow exit
                    if ($type === 'exit') {
                        feedback($indent, "Aborting...", false, true, '─« ');
                        $indent--;
                        break;
                    }

                    // Find valid parent types
                    $parentTypes = match ($type) {
                        'BRANCH' => ['HEADOFFICE'],
                        'DEPARTMENT' => ['HEADOFFICE', 'BRANCH'],
                        'LOCATION' => ['HEADOFFICE', 'BRANCH', 'DEPARTMENT', 'OWNER'],
                        default => null,
                    };

                    // Select Parent Entity
                    $indent++;
                    if (true) {
                        // Valid Parents
                        $validParents = array_flip(newCombineIdsByParentTypes($chainStructure, $parentTypes));
                        $parent = null;

                        if ($chainStructure
                            && $validParents
                            && $parentTypes
                            && isYes($indent, 'Is this ' . ucwords(strtolower($type)) . ' a Child of another Entity?', null, true, true)) {

                            // Select Parent Entity
                            $parent = select($indent, 'Select Parent Entity:', $validParents, null, true,
                                '#', false, true, false, false, false, true
                            );

                            // Allow exit
                            if ($parent === 'exit') {
                                feedback($indent, "Aborting...", false, true, '─« ');
                                $indent--;

                                feedback($indent, "Done", false, true, '─« ');
                                $indent--;
                                break;
                            }
                        }
                    }
                    $indent--;

                    // Create the Customer ---------------------------------------------------------------------------------

                    $indent++;
                    if (true) {
                        $customer = null;
                        do {
                            $customerAttributes = [
                                'name' => choice($indent, 'Name of ' . ucwords(strtolower($type)) . ' to add:', '@', true),
                                'number' => choice($indent, 'Cost Number:', '#', true, false),
                                'address1' => choice($indent, 'Street Address:', '@', true, false),
                                'address2' => null,
                                'postal_code' => choice($indent, 'Postal Code:', '#', true, false),
                                'city' => choice($indent, 'City:', '@', true, false),
                                'organization_number' => null,
                                'billing_contact' => $settingGroup->resolveSetting('billing_contact')
                                    ?? choice($indent, 'Name of Billing Contact:', '@', true, false),
                                'billing_customer_id' => null,
                                'setting_group_id' => $settingGroup->id,
                                'stack_id' => $stackId,
                                'type' => $type,
                                'time_zone' => $timeZone,
                                'locale_code' => $localeCode,
                                'currency' => $currency,
                                'language_code' => $languageCode,
                                'country_code' => $countryCode,
                                'region_id' => null,
                            ];
                            try {
                                $customer = Customer::newInstance(array_filter($customerAttributes, static function ($var) {
                                    return $var !== null;
                                }));
                                $customer->save();
                            } catch (Exception $e) {
                                warn($indent, "! Failed to create Entity, please try again");
                                addToLog('Error creating customer with attributes ' . json_encode($customerAttributes) . ' => ' . $e->getMessage(), $GLOBALS['log_file_indent']);
                                $customer = null;
                            }
                        } while (is_null($customer));

                        // Add relation to parent
                        if ($parent) {
                            $parentCustomer = Customer::get($parent);
                            if ($parentCustomer->makeParentOf($customer->id)) {
                                addToLog("Added [$customer->id: $customer->name] as Child of [$parent]", $GLOBALS['log_file_indent']);
                            } else {
                                warn($indent, "! Failed to make [$customer->id: $customer->name] a Child of [$parent]");
                                addToLog("Failed to add as Child of [$parent]", $GLOBALS['log_file_indent']);
                            }
                        }

                        // Based on the type, what about Customer Group?
                        $customerGroup = null;
                        switch ($type) {
                            case 'BRANCH':

                                // Create CustomerGroup with same name as SettingGroup
                                $customerGroup = CustomerGroup::newInstance([
                                    'name' => $customer->name,
                                ]);
                                $customerGroup->save();

                                // Add Parent to CustomerGroup
                                if ($parent) {
                                    $parentCustomer->addToCustomerGroup($customerGroup->id);
                                }

                                break;
                            case 'OWNER':

                                // Create CustomerGroup with same name as SettingGroup
                                $customerGroup = CustomerGroup::newInstance([
                                    'name' => $settingGroup->name,
                                ]);
                                $customerGroup->save();

                                break;

                            case 'DEPARTMENT':
                            case 'LOCATION':
                                // Return if no Parent
                                if (is_null($parent)) break;

                                // Get Parent Customer
                                $parentCustomer = Customer::get($parent);

                                // If Parent is OWNER or DEPARTMENT, put Customer in same CustomerGroup as Parent
                                if ($parentCustomer->type === 'OWNER' || $parentCustomer->type === 'DEPARTMENT' || $parentCustomer->type === 'BRANCH') {
                                    $customerGroups = $parentCustomer->activeCustomerGroups();

                                    // Make sure both Customer and Parent belong to the same CustomerGroup
                                    if (count($customerGroups) == 0) {
                                        $customerGroup = CustomerGroup::newInstance(['name' => $parentCustomer->name]);
                                        $parentCustomer->addToCustomerGroup($customerGroup->id);
                                    } elseif (count($customerGroups) == 1) {
                                        $customerGroup = $customerGroups[0];
                                    } else {
                                        // TODO: Allow exit?
                                        $customerGroup = CustomerGroup::get(
                                            select($indent, 'Select Customer Group:', modelList($customerGroups, 'name', 'id'), null, true, '#', false, true, false, false)
                                        );
                                    }
                                }
                                break;

                            default:
                                break;
                        }

                        // Add to Customer Group
                        if ($customerGroup) {
                            if ($customer->addToCustomerGroup($customerGroup->id)) {
                                addToLog("Added to Customer Group [$customerGroup->id: $customerGroup->name]", $GLOBALS['log_file_indent']);
                            } else {
                                warn($indent, "! Failed to add [$customer->id: $customer->name] to Customer Group [$customerGroup->id: $customerGroup->name]");
                                addToLog("Failed to add to Customer Group [$customerGroup->id: $customerGroup->name]", $GLOBALS['log_file_indent']);
                            }
                        }

                        // Copy from Template?
                        if (isYes($indent, 'Copy Setup from Template?', null, true, true)) {
                            // Check if Template exists in this Organization/Setting Group
//                            $templates = $chainStructure[$type] ?? [];
//                            $templates = array_flip(newCombineIdsByParentTypes($chainStructure, $type)); TODO: fix this!!!
                            $templates = [];

                            $indent++;
                            if (true) {
                                switch (count($templates)) {
                                    case 0: // No Customers of that Type found in this Organization's Structure, so..

                                        $template = null;

                                        // If no Organizations/SettingGroups exist, choose from all Customers
                                        if (count($allSettingGroups) == 0) {

                                            do {
                                                // ..select Template Customer, ..
                                                inform($indent, 'Select Template:', true, true, true, '─» ');
                                                $input = select($indent, '', modelList(Customer::getAll()->all(), 'name', 'id'), null, true, '#', false, true, false, false, false, true);

                                                // Allow exit
                                                if ($input === 'exit') {
                                                    feedback($indent, "Aborting...", false, true, '─« ');
                                                    $indent--;

                                                    feedback($indent, "Done", false, true, '─« ');
                                                    $indent--;
                                                    break;
                                                }

                                                $template = Customer::get($input);
                                            } while (is_null($template));

                                        } else {

                                            do {
                                                // ..select Template Setting Group, ..
                                                inform($indent, 'Select Organization Template:', true, true, true, '─» ');
                                                $templateSg = SettingGroup::get(
                                                    select($indent, '', modelList($allSettingGroups, 'name', 'id'), null, true, '#', false, true, false, false)
                                                );

                                                // TODO: swap to newGetChainStructure
                                                // ..and try to find Customer with equivalent Type, ..
                                                $templateEq = getChainStructure($templateSg->id)[$type][0] ?? null;
                                            } while (is_null($templateEq));

                                            $template = Customer::get(key($templateEq));
                                        }
                                        break;

                                    case 1: // There is only one Customer of that Type in this Organization's Structure, so use that

                                        $template = Customer::get($templates[0]);
                                        break;

                                    default: // There is more than one Customer of that Type in this Organization's Structure, so select one

                                        inform($indent, 'Select ' . ucwords(strtolower($type)) . ' Template:', true, true, true, '─» ');
                                        $template = SettingGroup::get(
                                            select($indent, '', modelList($templates, 'name', 'id'), null, true, '#', false, true, false, false)
                                        );
                                        break;
                                }

                                // Verify selection
                                inform($indent, "Using [$template->id: $template->name] as Template", true, true, true, '─» ');

                                // Clone from Template
                                cloneCustomer($indent, $customer, $template);

                                // Go back
                            }
                            $indent--;
                        }

                        // Created Successfully
                        feedback($indent, "$customer->name Created", false, true, '─« ');

                        // Go back
                    }
                    $indent--;

                    // Done
                    feedback($indent, "Entity Added to Organization", false, true, '─« ');
                } else {
                    // Done
                    feedback($indent, "Done", false, true, '─« ');
                }

                // Go back to Main Menu
                // -----------------------------------------------------------------------------------------------------
            }
            $indent--;

            break;

        case '4': // Copy from one Entity to another
            $indent++;
            if (true) {
                $allSettingGroups = SettingGroup::getAll()->all();
                $abort = false;

                // -----------------------------------------------------------------------------------------------------

                // Select Source Setting Group
                inform($indent, "Choose SOURCE Entity from Organization:", true, true, true, '─» ');
                $sourceSettingGroup = SettingGroup::get(
                    select($indent, '', modelList($allSettingGroups, 'name', 'id'), null, true, '#', false, true, false, false)
                );

                // Get all Members
                $allMembers = [];
                foreach ($sourceSettingGroup->members() as $member) {
                    $allMembers[] = ['id' => $member->id, 'name' => $member->name];
                }

                // Select Source Customer
                $indent++;
                if (true) {

                    $source = null;
                    do {
                        $source = Customer::get(
                            select($indent, 'Select Source Entity:', modelList($allMembers, 'name', 'id'), null, true, '#', false, true, true, false)
                        );
                    } while (is_null($source));

                    // Verify selection
                    if (!$abort) inform($indent, '{reset}Using [' . $GLOBALS['branch_colors'][$indent] . $source->id . '{reset}: ' . $GLOBALS['branch_colors'][$indent] . $source->name . '{reset}] as Source', true, false, true, '─« ');
                }
                $indent--;

                // -----------------------------------------------------------------------------------------------------

                // Select Target Setting Group
                inform($indent, "Choose TARGET Entity from Organization:", true, true, true, '─» ');
                $targetSettingGroup = SettingGroup::get(
                    select($indent, '', modelList($allSettingGroups, 'name', 'id'), null, true, '#', false, true, false, false)
                );

                // Get all Members
                $allMembers = [];
                foreach ($targetSettingGroup->members() as $member) {
                    $allMembers[] = ['id' => $member->id, 'name' => $member->name];
                }

                // Select Target Customer
                $indent++;
                if (true) {

                    $target = null;
                    do {
                        $target = Customer::get(
                            select($indent, 'Select Target Entity:', modelList($allMembers, 'name', 'id'), null, true, '#', false, true, true, false)
                        );

                        // Check if Source and Target are the same
                        if ($source->id === $target->id) {

                            if (count($allMembers) > 1) {
                                warn($indent, "! Source and Target cannot be the same Entity");
                                $target = null;
                            } else {
                                // Abort if only one Entity in selected Source Setting Group
                                $abort = true;
                                warn($indent, "Source and Target cannot be the same Entity", true, false, true, '─« ');

                                // Go back to Main Menu
                                break;
                            }
                        }
                    } while (is_null($target));

                    // Verify selection
                    inform($indent, '{reset}Using [' . $GLOBALS['branch_colors'][$indent] . $target->id . '{reset}: ' . $GLOBALS['branch_colors'][$indent] . $target->name . '{reset}] as Target', true, false, true, '─« ');
                }
                $indent--;

                // -----------------------------------------------------------------------------------------------------

                // Abort?
                if ($abort) {
                    warn($indent, "Aborting...", true, false, true, '─« ');
                    $indent--;
                    break;
                }

                // What to copy?
                if (true) {
                    $validOptions = validOptions($copyOptions, $source, $target);
                    $optionList = modelList($validOptions, 'name', 'id');

                    if ($validOptions) {
                        do {

                            $copy = select($indent, 'Choose what to Copy to Target (x to exit):', $optionList, null, true, '#', false, true, true, false, false, true);
                            while ($copy !== 'exit') {

                                // Get Source Data
                                $key = array_search($copy, array_column($validOptions, 'id'));
                                $data = $validOptions[$key];

                                // Copy from Source to Target
                                if ($data['models']) {
                                    copyModels($indent, $source, $target, $data);

                                    unset($optionList[array_search($copy, $optionList)]);
                                } else {
                                    warn($indent, "! No $copy to Copy", true, false, true, '─« ');
                                    $indent--;
                                    break;
                                }

                                // Done this set of Models
                                break;
                            }
                        } while ($copy !== 'exit' && $optionList);
                    } else {
                        warn($indent, "! No valid Options to Copy", true, false, true, '─« ');
                        $indent--;
                        break;
                    }
                }

                // Done
                feedback($indent, "Done", false, true, '─« ');

                // Go back to Main Menu
            }
            $indent--;

            break;

        case '5': // Other
            $indent++;
            if (true) {
                do {
                    $choice = menu('Select Operation:', [
                        1 => "Add User(s)",                         // Not started
                        2 => "Add Logo",                            // Not started
                        3 => "Command Line (Advanced)",             // Done
                        'x' => "Back to Main Menu"                  // Done
                    ], '#', $indent, false);

                    switch ($choice) {
                        case '1': // Add User(s)
                            $indent++;

                            warn($indent, "! Adding your own User to a User Group will remove your Admin permissions !", true, true, true);

                            if (true) {
                                // Add Users that should get full access to this Cluster
                                $users = [];
                                do {
                                    $found = false;

                                    $userId = choice($indent, 'Add User by e-mail address: (x=done)', '@', true);

                                    if ($userId === 'x') {
                                        feedback($indent,
                                            $GLOBALS['item'] . '[' . $GLOBALS['bold'] . count($users) . $GLOBALS['item'] . '] Users in List',
                                            true, true);
                                        break;
                                    }

                                    try {
                                        $user = User::get($userId);
                                        $users[] = $user;
                                        feedback($indent,
                                            $GLOBALS['item'] . 'User [' . $GLOBALS['bold'] . $user->first_name . ' ' . $user->last_name . $GLOBALS['item'] . '] Added to List',
                                            true, false);
                                        $found = true;
                                    }
                                    catch (Exception $e)
                                    {
                                        warn($indent, $GLOBALS['warn'] . "User [" . $GLOBALS['bold'] . $userId . $GLOBALS['warn'] . "] Not Found");
                                    }

                                    if (!$found) {
                                        try {
                                            $email = $userId;
                                            $user = User::newInstance(array_filter([
                                                'first_name' => choice($indent, 'First Name:', '@', true, false),
                                                'last_name' => choice($indent, 'Last Name:', '@', true, false),
                                                'email' => filter_var($userId, FILTER_VALIDATE_EMAIL) ? $userId : $email = choice($indent, 'Email:', '@', true, false),
//                                                'country_code' => choice($indent, 'Country Code:', '#', true, false, true),
//                                                'phone' => choice($indent, 'Phone:', '#', true, false, true),
                                                'language_code' => $languageCode,
                                            ], static function ($var) {
                                                return $var !== null;
                                            }));
                                            $user->save();

                                            $users[] = $user;
                                            feedback($indent,
                                                $GLOBALS['item'] . 'User [' . $GLOBALS['bold'] . $user->first_name . ' ' . $user->last_name . $GLOBALS['item'] . '] Added to List',
                                                true, false);
                                        } catch (Exception $e) {
                                            warn($indent, 'Failed to create User with email ['. $email .']');
                                        }
                                    }

                                    $user = null;
                                } while (!$user);

                                // Select where to add the Users
                                $allSettingGroups = SettingGroup::getAll()->all();
                                $abort = false;

                                // -----------------------------------------------------------------------------------------------------

                                // Select Destination Setting Group
                                inform($indent, "Select Organization to add Users:", true, true, true, '─» ');
                                $destinationSettingGroup = SettingGroup::get(
                                    select($indent, '', modelList($allSettingGroups, 'name', 'id'), null, true, '#', false, true, false, false)
                                );

                                // Ask for name of User Group to add Users to
                                $userGroupName = choice($indent, 'Add Users to User Group:', '@', true, true);

                                // Loop through all members of the Destination Setting Group
                                foreach ($destinationSettingGroup->members() as $member)
                                {
                                    $customer = Customer::get($member->id);
                                    $userGroup = $customer->findUserGroup(['name' => $userGroupName]);

                                    inform($indent, "$customer->name: ", true, true, false, '─» ');
                                    logg()->info('[');
                                    foreach ($users as $user) {
                                        addUser($user, $customer, $userGroup);
                                    }
                                    logg()->info("]\n");
                                }

                                // Select Destination Customer
//                                $indent++;
//                                if (true) {
//
//                                    $destination = null;
//                                    do {
//                                        $destination = Customer::get(
//                                            select($indent, 'Give access to Entity:', modelList($allMembers, 'name', 'id'), null, true, '#', false, true, true, false)
//                                        );
//                                    } while (is_null($destination));
//
//                                    // Verify selection
//                                    if (!$abort) inform($indent, '{reset}Using [' . $GLOBALS['branch_colors'][$indent] . $destination->id . '{reset}: ' . $GLOBALS['branch_colors'][$indent] . $destination->name . '{reset}] as Source', true, false, true, '─« ');
//                                }
//                                $indent--;

                                // -----------------------------------------------------------------------------------------------------

                                // Done
                                feedback($indent, "Done", false, true, '─« ');

                                // Go back to Main Menu
                            }
                            $indent--;

                            break;

                        case '2': // TODO
                            $indent++;
                            if (true) {
                                // Do something

                                // Done
                                feedback($indent, "Not implemented", false, true, '─« ');

                                // Go back to Main Menu
                            }
                            $indent--;

                            break;

                        case '3': // Command Line (Advanced)
                            $commandLine = true;

                            // Done
                            feedback($indent, "Accessing Command Line ('exit' to quit)", false, true, '─« ');
                            $indent--;
                            break 4;

                        case 'x':
                            inform($indent, 'Returning to Main Menu', true, false, true);
                            break;
                    }
                } while ($choice !== 'x');

                // Go back to Main Menu
            }
            $indent--;

            break;

            // Link Entities (relations)?

        case 'x': // Exit
            $indent--;
            break;
    }
//    echo PHP_EOL;
} while ($mode !== 'x');

// ---------------------------------------------------------------------------------------------------------------------

$stream = $GLOBALS['log_file'];

if (!is_null($stream))
{
    fclose($stream);

    feedback(0, $GLOBALS['item'] . 'Log saved to [' . $GLOBALS['bold'] . $GLOBALS['log_file_name'] . $GLOBALS['item'] . ']');

    $GLOBALS['log_file'] = null;
    $GLOBALS['log_file_name'] = null;
} else {
    feedback(0, $GLOBALS['item'] . "Exiting");
}

if (!$commandLine) exit;

// =====================================================================================================================
function cloneSettingGroup(int $indent, SettingGroup $newSettingGroup, SettingGroup $template): void
{
    // Custom Fields
    feedback($indent, $GLOBALS['item'] . 'Custom Fields: ', true, false, '─» ', false);
    logg()->info('[');
    foreach ($template->customFields() as $customField) {
        try {
            $newSettingGroup->addCustomField($customField) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        } catch (Exception $e) {
            logg()->info('{dred}' . '■');
            addToLog('Error copying custom field [' . $customField->id . ': ' . SettingGroupCustomField::get($customField->custom_field_id)->key . '] => ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        }
    }
    logg()->info("]\n");

    // Tariffs (incl. Rates and Rules)
    feedback($indent, $GLOBALS['item'] . 'Tariffs: ', true, false, '─» ', false);
    logg()->info('[');
    foreach ($template->tariffs() as $tariff) {
        try {
            $newSettingGroup->addTariff($tariff) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        } catch (Exception $e) {
            logg()->info('{dred}' . '■');
            addToLog('Error copying tariff [' . "$tariff->id: $tariff->name" . '] => ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        }
    }
    logg()->info("]\n");

    // Absence Types
    feedback($indent, $GLOBALS['item'] . 'Absence Types: ', true, false, '─» ', false);
    logg()->info('[');
    foreach ($template->absenceTypes() as $absenceType) {
        try {
            $newSettingGroup->addAbsenceType($absenceType) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        } catch (Exception $e) {
            logg()->info('{dred}' . '■');
            addToLog('Error copying absence type [' . "$absenceType->id: $absenceType->name" . '] => ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        }
    }
    logg()->info("]\n");

    // Contract Types
    feedback($indent, $GLOBALS['item'] . 'Contract Types: ', true, false, '─» ', false);
    logg()->info('[');
    foreach ($template->contractTypes() as $contractType) {
        try {
            $newSettingGroup->addContractType($contractType) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        } catch (Exception $e) {
            logg()->info('{dred}' . '■');
            addToLog('Error copying contract type [' . "$contractType->id: $contractType->name" . '] => ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        }
    }
    logg()->info("]\n");

    // Observers
    feedback($indent, $GLOBALS['item'] . 'Observers: ', true, false, '─» ', false);
    logg()->info('[');
    foreach ($template->observers() as $observer) {
        try {
            $newSettingGroup->copyObserver($observer) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        } catch (Exception $e) {
            logg()->info('{dred}' . '■');
            addToLog('Error copying observer [' . $observer->class . '] => ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        }
    }
    logg()->info("]\n");

    // Properties
    feedback($indent, $GLOBALS['item'] . 'Settings: ', true, false, '─» ', false);
    logg()->info('[');
    foreach ($template->properties() as $property) {
        try {
            $newSettingGroup->copyProperty($property) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        } catch (Exception $e) {
            logg()->info('{dred}' . '■');
            addToLog('Error copying property [' . "$property->id: $property->key" . '] => ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        }
    }
    logg()->info("]\n");

    // Done
    feedback($indent, "Done", false, true, '─« ');
}

function cloneCustomer(int $indent, Customer $target, Customer $template): void
{
    // Check if setting group is the same, if not, ask to use the setting_group from template instead
    if ($template->setting_group_id !== $target->setting_group_id) {
        warn($indent, "! Target Entity is not in the same Organization as the Source Entity");
        warn($indent, "! Some items may not be copied correctly");
    }

    // Gather stuff
    $products = $template->findProducts('customer');
//    $customerGroups = $template->activeCustomerGroups();
    $kpiTypes = $template->kpiTypes();
    $payTypeLinks = $template->activePayTypeLinks();
    $hrFileTypes = $template->fileTypes();
    $reports = $template->reports();
    $businessUnits = $template->businessUnits();
    $checklists = $template->checklistTemplates();
    $todoStatuses = $template->todoStatuses();
    $userGroups = $template->userGroups();
    $positions = $template->positions();
    $qualifications = $template->qualifications();
    $roles = $template->roles();
    $defaultHrFiles = $template->defaultHrFiles();
    $logo = $template->getLogo();
    $properties = $template->properties();
//    $relations = $template->relationships();

    // Products
    if (count($products) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Products: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($products as $product) {
            addProduct($target, $product);
        }
        logg()->info("]\n");
    }

    // Customer Groups
//    if (count($customerGroups) > 0) {
//        feedback($indent, $GLOBALS['item'] . 'Customer Groups: ', true, false, '─» ', false);
//        logg()->info('[');
//        foreach ($customerGroups as $customerGroup) {
//            copyCustomerGroup($target, $customerGroup);
//        }
//        logg()->info("]\n");
//    }

    // KPI Types
    if (count($kpiTypes) > 0) {
        feedback($indent, $GLOBALS['item'] . 'KPI Types: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($kpiTypes as $kpiType) {
            copyKpiType($target, $kpiType);
        }
        logg()->info("]\n");
    }

    // Pay Type Links
    if (count($payTypeLinks) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Pay Type Links: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($payTypeLinks as $payTypeLink) {
            copyPayTypeLink($target, $payTypeLink);
        }
        logg()->info("]\n");
    }

    // HR File Types
    if (count($hrFileTypes) > 0) {
        feedback($indent, $GLOBALS['item'] . 'HR File Types: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($hrFileTypes as $hrFileType) {
            copyFileType($target, $hrFileType);
        }
        logg()->info("]\n");
    }

    // Reports
    if (count($reports) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Reports: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($reports as $report) {
            copyReport($target, $report);
        }
        logg()->info("]\n");
    }

    // Business Units
    if (count($businessUnits) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Business Units: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($businessUnits as $businessUnit) {
            copyBusinessUnit($target, $businessUnit);
        }
        logg()->info("]\n");
    }

    // Checklists
    if (count($checklists) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Checklists: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($checklists as $checklist) {
            copyChecklistTemplate($target, $checklist);
        }
        logg()->info("]\n");
    }

    // Todo Statuses
    if (count($todoStatuses) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Todo Statuses: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($todoStatuses as $todoStatus) {
            copyTodoStatus($target, $todoStatus);
        }
        logg()->info("]\n");
    }

    // User Groups
    if (count($userGroups) > 0) {
        feedback($indent, $GLOBALS['item'] . 'User Groups: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($userGroups as $userGroup) {
            copyUserGroup($target, $userGroup);
        }
        logg()->info("]\n");
    }

    // Positions
    if (count($positions) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Positions: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($positions as $position) {
            copyPosition($target, $position);
        }
        logg()->info("]\n");
    }

    // Qualifications
    if (count($qualifications) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Qualifications: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($qualifications as $qualification) {
            copyQualification($target, $qualification);
        }
        logg()->info("]\n");
    }

    // Roles
    if (count($roles) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Roles: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($roles as $role) {
            copyRole($target, $role);
        }
        logg()->info("]\n");
    }

    // Default HR Files
    if (count($defaultHrFiles) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Default HR Files: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($defaultHrFiles as $defaultHrFile) {
            copyDefaultHrFile($target, $defaultHrFile);
        }
        logg()->info("]\n");
    }

    // Logo
    if ($logo) {
        feedback($indent, $GLOBALS['item'] . 'Logo: ', true, false, '─» ', false);
        logg()->info('[');
        copyLogo($target, $logo);
        logg()->info("]\n");
    }

    // Properties
    if (count($properties) > 0) {
        feedback($indent, $GLOBALS['item'] . 'Properties: ', true, false, '─» ', false);
        logg()->info('[');
        foreach ($properties as $property) {
            copyPropery($target, $template, $property);
        }
        logg()->info("]\n");
    }

    // Relations
//    if (count($relations) > 0) {
//        feedback($indent, $GLOBALS['item'] . 'Relations: ', true, false, '─» ', false);
//        logg()->info('[');
//        foreach ($relations as $relation) {
//            copyRelation($target, $template, $relation);
//        }
//        logg()->info("]\n");
//    }

    // Done
    feedback($indent, "Done", false, true, '─« ');
}

function validOptions(array $allOptions, Customer $source, Customer $destination): array
{
    $validOptions = [];

    foreach ($allOptions as $option)
    {
        $models = match ($option['id']) {
            'products' => $source->findProducts('customer'),
            'customer_groups' => $source->activeCustomerGroups(),
            'kpi_types' => $source->kpiTypes(),
            'pay_type_links' => $source->payTypeLinks(),
            'hr_file_types' => $source->fileTypes(),
            'reports' => $source->reports(),
            'business_units' => $source->businessUnits(),
            'checklists' => $source->checklistTemplates(),
            'todo_statuses' => $source->todoStatuses(),
            'user_groups' => $source->userGroups(),
            'positions' => $source->positions(),
            'qualifications' => $source->qualifications(),
            'roles' => $source->roles(),
            'default_hr_files' => $source->defaultHrFiles(),
            'logo' => $source->getLogo(),
            'properties' => $source->properties(),
            'links' => $source->relationships(),
            'setting_group' => $source->setting_group_id !== $destination->setting_group_id ? [$source->settingGroup()] : null,
            default => null,
        };

        if ($models) {
            $validOptions[] = ['id' => $option['id'], 'name' => $option['name'], 'models' => $models];
        }
    }

    return $validOptions;
}

function copyModels(int &$indent, Customer $source, Customer $target, array $modelType): void
{
    $indent++;

    $models = $modelType['models'];

//    warn($indent, '! Copying ' . $modelType['name'] . ' from Source to Target', true, false, true, '─» ');

    // Special case for Organizational Settings (SettingGroup)
    if ($modelType['id'] == 'setting_group') {
        cloneSettingGroup($indent, $target->settingGroup(), $source->settingGroup());

        $indent--;
        return;
    }

    feedback($indent, $GLOBALS['branch_colors'][$indent] . 'Copying ' . $modelType['name'], false, false, '─» ', false);
    logg()->info(' [');

    // Special case for Logo
    if ($modelType['id'] == 'logo') {
        copyLogo($target, $models[0]);

        logg()->info("]\n");
        $indent--;
        return;
    }

    // Copy all other models based on type
    foreach ($models as $model)
    {
        switch ($model::class) {
            case Product::class:
                addProduct($target, $model);
                break;
            case CustomerGroup::class:
                copyCustomerGroup($target, $model);
                break;
            case CustomerKpiType::class:
                copyKpiType($target, $model);
                break;
            case CustomerPayTypeLink::class:
                copyPayTypeLink($target, $model);
                break;
            case CustomerFileType::class:
                copyFileType($target, $model);
                break;
            case CustomerReport::class:
                copyReport($target, $model);
                break;
            case CustomerBusinessUnit::class:
                copyBusinessUnit($target, $model);
                break;
            case CustomerChecklist::class:
                copyChecklistTemplate($target, $model);
                break;
            case CustomerTodoStatus::class:
                copyTodoStatus($target, $model);
                break;
            case CustomerUserGroup::class:
                copyUserGroup($target, $model);
                break;
            case CustomerPosition::class:
                copyPosition($target, $model);
                break;
            case CustomerQualification::class:
                copyQualification($target, $model);
                break;
            case CustomerRole::class:
                copyRole($target, $model);
                break;
            case CustomerDefaultHrFile::class:
                copyDefaultHrFile($target, $model);
                break;
            case Property::class:
                copyPropery($target, $source, $model);
                break;
            case CustomerLink::class:
                copyRelation($target, $source, $model);
                break;
            default:
                break;
        }
    }

    logg()->info("]\n");
    $indent--;
}

function addProduct(Customer $target, Product $product): bool {
    try {
        $target->addProduct($product->name) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Product ' . $product->{$product->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyCustomerGroup(Customer $target, CustomerGroup $customerGroup): bool {
    try {
        $target->addToCustomerGroup($customerGroup->id) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying CustomerGroup ' . $customerGroup->{$customerGroup->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyKpiType(Customer $target, CustomerKpiType $kpiType): bool {
    try {
        $target->addKpiType($kpiType) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying KPI Type ' . $kpiType->{$kpiType->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyPayTypeLink(Customer $target, CustomerPayTypeLink $payTypeLink): bool {
    try {
        $target->copyPayTypeLink($payTypeLink, true, true, true, false) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying PayTypeLink ' . $payTypeLink->{$payTypeLink->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyFileType(Customer $target, CustomerFileType $fileType): bool {
    try {
        $target->copyFileType($fileType) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying FileType ' . $fileType->{$fileType->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyReport(Customer $target, CustomerReport $report): bool {
    try {
        $target->copyReport($report) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Report ' . $report->{$report->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyBusinessUnit(Customer $target, CustomerBusinessUnit $businessUnit): bool {
    try {
        // TODO: Replace with copyBusinessUnit to handle nested BusinessUnits
        $target->addBusinessUnit($businessUnit->name, false, $businessUnit->color) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying BusinessUnit ' . $businessUnit->{$businessUnit->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyChecklistTemplate(Customer $target, CustomerChecklist $checklist): bool {
    try {
        $target->copyChecklist($checklist) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying ChecklistTemplate ' . $checklist->{$checklist->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyTodoStatus(Customer $target, CustomerTodoStatus $todoStatus): bool {
    try {
        $target->copyTodoStatus($todoStatus) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying TodoStatus ' . $todoStatus->{$todoStatus->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyUserGroup(Customer $target, CustomerUserGroup $userGroup): bool {
    try {
        $target->copyUserGroup($userGroup) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying UserGroup ' . $userGroup->{$userGroup->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyPosition(Customer $target, CustomerPosition $position): bool {
    try {
        $target->copyPosition($position) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Position ' . $position->{$position->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyQualification(Customer $target, CustomerQualification $qualification): bool {
    try {
        $target->addQualification($qualification->name) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Qualification ' . $qualification->{$qualification->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyRole(Customer $target, CustomerRole $role): bool {
    try {
        // Continue if role is not a root role
        if (!is_null($role->parent_id)) {
            return false;
        }

        $target->addRole($role) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Role ' . $role->{$role->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyDefaultHrFile(Customer $target, CustomerDefaultHrFile $defaultHrFile): bool {
    try {
        $target->copyDefaultHrFile($defaultHrFile) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying DefaultHrFile ' . $defaultHrFile->{$defaultHrFile->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyPropery(Customer $target, Customer $source, Property $property): bool {
    try {
        $target->copyProperty($property, $source) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Property ' . $property->{$property->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyRelation(Customer $target, Customer $source, CustomerLink $relation): bool {
    try {
        $target->copyRelation($relation, $source) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Relationship ' . $relation->{$relation->keyName} . ' => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function copyLogo(Customer $target, $logo): bool {
    try {
        $target->setLogo($logo) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
        return true;
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog('Error copying Logo => ' . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
        return false;
    }
}

function addUser(User $user, Customer $target, ?CustomerUserGroup $userGroup): void {

    // Skip if UserGroup does not exist
    if (is_null($userGroup)) {
        logg()->info('{dred}' . '×');
        return;
    }

    // Access
    try {
        $target->addUserAccess($user) ? logg()->info('{dgreen}' . '■') : logg()->info('{dyellow}' . '■');
    } catch (Exception $e) {
        logg()->info('{dred}' . '■');
        addToLog("Failed to add User Access for User [$user->email] => " . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
    }

    // User Group
    try {
        $userGroup->addMember($user->id) ? logg()->info('{dgreen}' . '□') : logg()->info('{dyellow}' . '□');

    } catch (Exception $e) {
        logg()->info('{dred}' . '□');
        addToLog("Failed to add User [$user->email] to UserGroup [$userGroup->id: $userGroup->name]  => " . $e->getCode() . ': ' . $e->getMessage(), $GLOBALS['log_file_indent'] + 1);
    }
}