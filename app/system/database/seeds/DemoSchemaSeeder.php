<?php

namespace System\Database\Seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSchemaSeeder extends Seeder
{
    protected $recordsPath = __DIR__.'/../records';

    /**
     * Run the demo schema seeds.
     * @return void
     */
    public function run()
    {
        if (!DatabaseSeeder::$seedDemo) return;

        $this->seedCategories();

        $this->seedMenuOptions();

        $this->seedMenuItems();
    }

    protected function seedWorkingHours()
    {
        foreach (['opening', 'delivery', 'collection'] as $type) {
            foreach (['0', '1', '2', '3', '4', '5', '6'] as $day) {
                DB::table('working_hours')->insert([
                    'location_id' => DatabaseSeeder::$locationId,
                    'weekday' => $day,
                    'type' => $type,
                    'opening_time' => '00:00',
                    'closing_time' => '23:59',
                    'status' => 1,
                ]);
            }
        }
    }

    protected function seedCategories()
    {
        if (DB::table('categories')->count())
            return;

        foreach ($this->getSeedRecords('categories') as $record) {
            DB::table('locationables')->insert([
                'location_id' => DatabaseSeeder::$locationId,
                'locationable_id' => DB::table('categories')->insertGetId($record),
                'locationable_type' => 'categories',
            ]);
        }
    }

    protected function seedMenuOptions()
    {
        if (DB::table('menu_options')->count())
            return;

        foreach ($this->getSeedRecords('menu_options') as $menuOption) {
            $optionId = DB::table('menu_options')->insertGetId(array_except($menuOption, 'option_values'));

            foreach (array_get($menuOption, 'option_values') as $optionValue) {
                DB::table('menu_option_values')->insert(array_merge($optionValue, [
                    'option_id' => $optionId,
                ]));
            }

            DB::table('locationables')->insert([
                'location_id' => DatabaseSeeder::$locationId,
                'locationable_id' => $optionId,
                'locationable_type' => 'menu_options',
            ]);
        }
    }

    protected function seedMenuItems()
    {
        if (DB::table('menus')->count())
            return;

        foreach ($this->getSeedRecords('menus') as $menu) {
            $menuId = DB::table('menus')->insertGetId(array_except($menu, 'menu_options'));

            foreach (array_get($menu, 'menu_options', []) as $name) {
                $option = DB::table('menu_options')->where('option_name', $name)->first();

                $menuOptionId = DB::table('menu_item_options')->insertGetId([
                    'option_id' => $option->option_id,
                    'menu_id' => $menuId,
                ]);

                $optionValues = DB::table('menu_option_values')->where('option_id', $option->option_id)->get();

                foreach ($optionValues as $optionValue) {
                    DB::table('menu_item_option_values')->insertGetId([
                        'menu_option_id' => $menuOptionId,
                        'option_value_id' => $optionValue->option_value_id,
                        'new_price' => $optionValue->price,
                        'quantity' => 0,
                        'subtract_stock' => 0,
                        'priority' => $optionValue->priority,
                    ]);
                }
            }

            DB::table('locationables')->insert([
                'location_id' => DatabaseSeeder::$locationId,
                'locationable_id' => $menuId,
                'locationable_type' => 'menus',
            ]);
        }
    }

    protected function getSeedRecords($name)
    {
        return json_decode(file_get_contents($this->recordsPath.'/'.$name.'.json'), TRUE);
    }
}
