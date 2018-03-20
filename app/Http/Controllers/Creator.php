<?php

namespace App\Http\Controllers;

use App\Group;
use App\School;
use App\Tag;
use Illuminate\Http\Request;
use \App\Category;
use \App\Topic;
use Illuminate\Support\Traits\CapsuleManagerTrait;
use \App\Reference;
use \App\User;
use Monolog\Processor\TagProcessor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
Use DB;
use ZipArchive;


class Creator extends Controller{
    public function categories(){
        return view('creator_categories_interface');
    }

    public function topics(){
        //COMMENT: filter by accepted status.
        $C = Category::all();
        $tags = Tag::all();
        $categories = [];
        for($i = 0; $i < count($C); $i++){
            $categories[$i] = $C[$i] -> name;
        }
        return view('creator_topics_interface', compact(['categories', 'tags']));
    }

    public function statistics(){
        return view('general_statistics');
    }

    public function usersRanking(){
        $ranked_users = DB::select('SELECT U.username AS username, AVG(P.points) AS points FROM schools E JOIN groups G ON E.id=G.school_id JOIN users U ON G.id=U.group_id JOIN marks P ON U.id=P.user_id GROUP BY P.user_id ORDER BY AVG(points) DESC ');
        $non_ranked_users = DB::select('SELECT U.username FROM users U WHERE NOT EXISTS ( SELECT user_id FROM marks where user_id = U.id )');
        return view('users_ranking', compact(['ranked_users', 'non_ranked_users']));
    }

    public function groupsRanking(){
        $ranked_groups = DB::select('SELECT G.name AS name, AVG(P.points) AS points FROM groups G JOIN users U ON G.id=U.group_id JOIN marks P ON U.id=P.user_id GROUP BY P.group_id ORDER BY AVG(points) DESC ');
        $non_ranked_groups = DB::select('SELECT G.name FROM groups G WHERE NOT EXISTS ( SELECT group_id FROM marks where group_id = G.id) and G.name != \'Sin Asignar\'');
        return view('groups_ranking', compact(['ranked_groups', 'non_ranked_groups']));
    }

    public function schoolsRanking(){
        $ranked_schools = DB::select('SELECT E.name AS name, AVG(P.points) AS points FROM schools E JOIN groups G ON E.id=G.school_id JOIN users U ON G.id=U.group_id JOIN marks P ON U.id=P.user_id GROUP BY P.school_id ORDER BY AVG(points) DESC;');
        $non_ranked_schools = DB::select('SELECT S.name FROM schools S WHERE NOT EXISTS ( SELECT school_id FROM marks where school_id = S.id) and S.name != \'Sin Asignar\'');
        return view('schools_ranking', compact(['ranked_schools', 'non_ranked_schools']));
    }

    public function userStatistics($id){
        $user_id = $id;
        return view('user_ranking', compact(['user_id']));
    }

    public function groupStatistics($name){
        $group_name = $name;
        return view('group_statistics', compact(['group_name']));
    }

    public function schoolStatistics($name){
        $school_name = $name;
        return view('school_statistics', compact(['school_name']));
    }

    public function getUserTheoryStatistics($user){
        $user_name = $user;
        $categories = Category::all();
        $categories_array = [];
        $topics_array = [];
        $total_topics = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i] = [];
            for($j = 0; $j < count($topics); $j++){
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        $theory_glances = User::where('username', '=', $user_name) -> first() -> glances() -> where('type', '=', 'T') -> get();
        $theory_glances_array = [];
        for($i = 0; $i < count($theory_glances); $i++){
            $topic_name = Topic::where('id', '=', $theory_glances[$i] -> topic_id) -> first();
            $theory_glances_array[$i] = $topic_name;
        }
        return view('user_statistics_theory_table', compact(['user_id', 'categories_array', 'topics_array', 'theory_glances_array', 'total_topics']));
    }

    public function getGroupTheoryStatistics($name){
        $group_name         = $name;
        $group              = Group::where('name', '=', $group_name) -> first();
        $categories         = Category::all();
        $categories_array   = [];
        $topics_array       = [];
        $total_topics       = 0;
        $percentages        = [];
        $people             = [];
        $visualizations     = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i]   = [];
            $percentages[$i]    = [];
            $users_in_group  = User::where('group_id', '=', $group -> id) -> get();
            $users_in_group_count  = count($users_in_group);
            for($j = 0; $j < count($topics); $j++){
                $seen = DB::select('SELECT COUNT(*) as many FROM glance_user as GU, glances as G where GU.group_id = ? and G.type = ?  and G.topic_id = ? and G.id = Gu.glance_id', [$group -> id, 'T', $topics[$j] -> id]);
                $seen = $seen[0] -> many;
                $people[$i][$j] = $seen;
                $visualizations += $seen;
                $percentages[$i][$j] = $users_in_group_count > 0 ? $seen * 100 / $users_in_group_count : 0;
                $percentages[$i][$j] = number_format((float)$percentages[$i][$j], 2, '.', '');
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        return view('group_statistics_theory_table', compact(['categories_array', 'topics_array', 'percentages', 'people', 'visualizations']));
    }

    public function getGroupQuestionnaireStatistics($name){
        $group_name         = $name;
        $group              = Group::where('name', '=', $group_name) -> first();
        $categories         = Category::all();
        $categories_array   = [];
        $topics_array       = [];
        $total_topics       = 0;
        $percentages        = [];
        $people             = [];
        $visualizations     = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i]   = [];
            $percentages[$i]    = [];
            $users_in_group  = User::where('group_id', '=', $group -> id) -> get();
            $users_in_group_count  = count($users_in_group);
            for($j = 0; $j < count($topics); $j++){
                $seen = DB::select('SELECT COUNT(*) as many FROM glance_user as GU, glances as G where GU.group_id = ? and G.type = ?  and G.topic_id = ? and G.id = Gu.glance_id', [$group -> id, 'C', $topics[$j] -> id]);
                $seen = $seen[0] -> many;
                $people[$i][$j] = $seen;
                $visualizations += $seen;
                $percentages[$i][$j] = $users_in_group_count > 0 ? $seen * 100 / $users_in_group_count : 0;
                $percentages[$i][$j] = number_format((float)$percentages[$i][$j], 2, '.', '');
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        return view('group_statistics_questionnaire_table', compact(['categories_array', 'topics_array', 'percentages', 'people', 'users_in_group_count', 'visualizations']));
    }

    public function getGroupSimulationStatistics($name){
        $group_name         = $name;
        $group              = Group::where('name', '=', $group_name) -> first();
        $categories         = Category::all();
        $categories_array   = [];
        $topics_array       = [];
        $total_topics       = 0;
        $percentages        = [];
        $people             = [];
        $visualizations     = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i]   = [];
            $percentages[$i]    = [];
            $users_in_group  = User::where('group_id', '=', $group -> id) -> get();
            $users_in_group_count  = count($users_in_group);
            for($j = 0; $j < count($topics); $j++){
                $seen = DB::select('SELECT COUNT(*) as many FROM glance_user as GU, glances as G where GU.group_id = ? and G.type = ?  and G.topic_id = ? and G.id = Gu.glance_id', [$group -> id, 'S', $topics[$j] -> id]);
                $seen = $seen[0] -> many;
                $people[$i][$j] = $seen;
                $visualizations += $seen;
                $percentages[$i][$j] = $users_in_group_count > 0 ? $seen * 100 / $users_in_group_count : 0;
                $percentages[$i][$j] = number_format((float)$percentages[$i][$j], 2, '.', '');
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        return view('group_statistics_simulation_table', compact(['categories_array', 'topics_array', 'percentages', 'people', 'users_in_group_count', 'visualizations']));
    }


    public function getSchoolTheoryStatistics($name){
        $school_name         = $name;
        $school              = School::where('name', '=', $school_name) -> first();
        $categories         = Category::all();
        $categories_array   = [];
        $topics_array       = [];
        $total_topics       = 0;
        $percentages        = [];
        $people             = [];
        $visualizations     = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i]   = [];
            $percentages[$i]    = [];
            $users_in_school  = User::where('school_id', '=', $school -> id) -> get();
            $users_in_school_count  = count($users_in_school);
            for($j = 0; $j < count($topics); $j++){
                $seen = DB::select('SELECT COUNT(*) as many FROM glance_user as GU, glances as G where GU.school_id = ? and G.type = ?  and G.topic_id = ? and G.id = Gu.glance_id', [$school -> id, 'T', $topics[$j] -> id]);
                $seen = $seen[0] -> many;
                $people[$i][$j] = $seen;
                $visualizations += $seen;
                $percentages[$i][$j] = $users_in_school_count > 0 ? $seen * 100 / $users_in_school_count : 0;
                $percentages[$i][$j] = number_format((float)$percentages[$i][$j], 2, '.', '');
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        return view('school_statistics_theory_table', compact(['categories_array', 'topics_array', 'percentages', 'people', 'visualizations']));
    }

    public function getSchoolQuestionnaireStatistics($name){
        $school_name        = $name;
        $school             = School::where('name', '=', $school_name) -> first();
        $categories         = Category::all();
        $categories_array   = [];
        $topics_array       = [];
        $total_topics       = 0;
        $percentages        = [];
        $people             = [];
        $visualizations     = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i]   = [];
            $percentages[$i]    = [];
            $users_in_school  = User::where('group_id', '=', $school -> id) -> get();
            $users_in_school_count  = count($users_in_school);
            for($j = 0; $j < count($topics); $j++){
                $seen = DB::select('SELECT COUNT(*) as many FROM glance_user as GU, glances as G where GU.school_id = ? and G.type = ?  and G.topic_id = ? and G.id = Gu.glance_id', [$school -> id, 'C', $topics[$j] -> id]);
                $seen = $seen[0] -> many;
                $people[$i][$j] = $seen;
                $visualizations += $seen;
                $percentages[$i][$j] = $users_in_school_count > 0 ? $seen * 100 / $users_in_school_count : 0;
                $percentages[$i][$j] = number_format((float)$percentages[$i][$j], 2, '.', '');
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        return view('school_statistics_questionnaire_table', compact(['categories_array', 'topics_array', 'percentages', 'people', 'users_in_group_count', 'visualizations']));
    }

    public function getSchoolSimulationStatistics($name){
        $schoo         = $name;
        $group              = Group::where('name', '=', $group_name) -> first();
        $categories         = Category::all();
        $categories_array   = [];
        $topics_array       = [];
        $total_topics       = 0;
        $percentages        = [];
        $people             = [];
        $visualizations     = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i]   = [];
            $percentages[$i]    = [];
            $users_in_group  = User::where('group_id', '=', $group -> id) -> get();
            $users_in_group_count  = count($users_in_group);
            for($j = 0; $j < count($topics); $j++){
                $seen = DB::select('SELECT COUNT(*) as many FROM glance_user as GU, glances as G where GU.group_id = ? and G.type = ?  and G.topic_id = ? and G.id = Gu.glance_id', [$group -> id, 'S', $topics[$j] -> id]);
                $seen = $seen[0] -> many;
                $people[$i][$j] = $seen;
                $visualizations += $seen;
                $percentages[$i][$j] = $users_in_group_count > 0 ? $seen * 100 / $users_in_group_count : 0;
                $percentages[$i][$j] = number_format((float)$percentages[$i][$j], 2, '.', '');
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        return view('group_statistics_simulation_table', compact(['categories_array', 'topics_array', 'percentages', 'people', 'users_in_group_count', 'visualizations']));
    }

    public function getUserQuestionnaireStatistics($user){
        $user_name = $user;
        $categories = Category::all();
        $categories_array = [];
        $topics_array = [];
        $total_topics = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i] = [];
            for($j = 0; $j < count($topics); $j++){
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        $questionnaire_glances = User::where('username', '=', $user_name) -> first() -> glances() -> where('type', '=', 'C') -> get();
        $questionnaire_glances_array = [];
        for($i = 0; $i < count($questionnaire_glances); $i++){
            $topic_name = Topic::where('id', '=', $questionnaire_glances[$i] -> topic_id) -> first();
            $questionnaire_glances_array[$i] = $topic_name;
        }
        return view('user_statistics_questionnaire_table', compact(['user_id', 'categories_array', 'topics_array', 'questionnaire_glances_array', 'total_topics']));
    }

    public function getUserSimulationStatistics($user){
        $user_name = $user;
        $categories = Category::all();
        $categories_array = [];
        $topics_array = [];
        $total_topics = 0;
        for($i = 0; $i < count($categories); $i++){
            $categories_array[$i] = $categories[$i] -> name;
            $topics = $categories[$i] -> topics() -> get();
            $topics_array[$i] = [];
            for($j = 0; $j < count($topics); $j++){
                $topics_array[$i][$j] = $topics[$j] -> name;
                $total_topics++;
            }
        }
        $simulation_glances = User::where('username', '=', $user_name) -> first() -> glances() -> where('type', '=', 'S') -> get();
        $simulation_glances_array = [];
        for($i = 0; $i < count($simulation_glances); $i++){
            $topic_name = Topic::where('id', '=', $simulation_glances[$i] -> topic_id) -> first();
            $simulation_glances_array[$i] = $topic_name;
        }
        return view('user_statistics_simulation_table', compact(['user_id', 'categories_array', 'topics_array', 'simulation_glances_array', 'total_topics']));
    }

    public function categoryList(){
        $user_id = session('user_id');
        $categories = Category::where('user_id', '=', $user_id) -> get();
        $names = [];
        for($i = 0; $i < count($categories); $i++){
            $names[$i] = $categories[$i] -> name;
        }
        return view('creator_category_list', compact('names'));
    }

    public function topicList(){
        $user_id            = session('user_id');
        $T                  = Topic::where('user_id', '=',$user_id) -> get();
        $topics             = [];
        $topics_categories  = [];
        $topics_tags        = [];
        $categories         = [];
        for($i = 0; $i < count($T); $i++){
            $topics[$i]             = $T[$i] -> name;
            $topics_categories[$i]  = Category::where('id', '=', $T[$i] -> category_id) -> first() -> name;
            $topics_tags[$i]        = Topic::where('name', '=', $topics[$i]) -> get() -> first() -> tags() -> get();
        }
        $C = Category::all();
        $categories = [];
        for($i = 0; $i < count($C); $i++){
            $categories[$i] = $C[$i] -> name;
        }
        return view('creator_topic_list', compact(['topics', 'topics_categories', 'topics_tags', 'categories']));
    }

    public function categoryListJSON(){
        $categories = Category::all();
        return compact('categories');
    }

    public function registerCategory(Request $request){
        Validator::extend('new_category', function($field,$value,$parameters){
            $category = Category::where('name', '=', $value) -> first();
            return $category ==  null;
        });

        Validator::extend('alpha_spaces', function($attribute, $value)
        {
            return preg_match('/^[\pL\s]+$/u', $value);
        });

        $messages = array(
            'required'              => 'El nombre de la categoria es requerido',
            'new_category'          => 'Nombre existente, elige otro.',
            'alpha_spaces'          => 'Solo se permiten letras y espacios.'
        );

        $validator = Validator::make($request->all(), [
            'category_name' => 'required|new_category|alpha_spaces',
        ], $messages);

        $user_id = session('user_id');
        $creator = User::where('id', '=', $user_id) -> get() -> first() -> creator;
        if ($validator->passes()) {
            $category = new Category([
                'user_id'       => $user_id,
                'creator_id'    => $creator -> id,
                'name'          => $request -> category_name,
                'status'        => 'pending',
            ]);
            $category -> save();
            Storage::disk('local') -> makeDirectory('public/'.$category -> name);
            return response()->json(['success'=>'OK.']);
        }
        return response()->json(['error'=>$validator->errors()->all()]);
    }

    public function registerTopic(Request $request){

        Validator::extend('new_topic', function($field,$value,$parameters){
            $topic = Topic::where('name', '=', $value) -> first();
            return $topic ==  null;
        });

        Validator::extend('not_default', function($field,$value,$parameters){
            return $value != 'Selecciona la categoria';
        });

        Validator::extend('alpha_spaces', function($attribute, $value)
        {
            return preg_match('/^[\pL\s]+$/u', $value);
        });

        $messages = array(
            'required'              => 'El nombre del tema es requerido',
            'new_topic'             => 'Nombre existente, elige otro.',
            'alpha_spaces'          => 'Solo se permiten letras y espacios.',
            'not_default'           => 'Selecciona una categoria',
        );

        $validator = Validator::make($request->all(), [
            'topic_name'    => 'required|new_topic|alpha_spaces',
            'category_name' => 'not_default',
        ], $messages);
        if ($validator->passes()) {
            $user_id = session('user_id');
            $creator = User::where('id', '=', $user_id) -> get() -> first() -> creator;
            $category = Category::where('name', '=', $request->category_name)->first();
            $topic = new Topic([
                'user_id'       => $user_id,
                'creator_id'    => $creator -> id,
                'name'          => $request->topic_name,
                'status'        => 'pending',
                'category_id'   => $category->id]
            );
            $topic->save();
            if($request -> tags != null){
                for($i = 0; $i < count($request -> tags); $i++){
                    $tag = Tag::firstOrNew(array(
                        'name'          => $request -> tags[$i],
                    ));
                    $tag -> save();
                    $topic -> tags() -> attach($tag -> id, [
                        'topic_id' => $topic -> id,
                        'category_id' => $topic -> category_id
                    ]);
                }
            }
            Storage::disk('local')->makeDirectory('public/' . $category->name . '/' . $topic->name);
            Storage::disk('local')->makeDirectory('public/' . $category->name . '/' . $topic->name . '/Simulacion');
            Storage::disk('local')->makeDirectory('public/' . $category->name . '/' . $topic->name . '/Teoria');
            Storage::disk('local')->makeDirectory('public/' . $category->name . '/' . $topic->name . '/Cuestionario');
            return response()->json(['success'=>'OK.']);
        }
        return response()->json(['error'=>$validator->errors()->all()]);
    }

    public function deleteCategory(Request $request){
        $category = Category::where('name', '=', $request -> category_name) -> first();
        $category -> delete();
        Storage::disk('local') -> deleteDirectory('public/'.$category -> name);
        return response() -> json(['success' => 'OK']);
    }

    public function deleteTopic(Request $request){
        $topic = Topic::where('name', '=', $request -> topic_name) -> first();
        $category = Category::where('id', '=', $topic->category_id) -> first();
        $topic -> delete();
        Storage::disk('local') -> deleteDirectory('public/'. $category -> name. '/'.$topic -> name);
        return response() -> json(['success' => 'OK']);
    }

    public function editCategory(Request $request){
        Validator::extend('new_category', function($field,$value,$parameters){
            $old = $parameters[0];
            $new = $parameters[1];
            $category = Category::where('name', '=', $new) -> first();
            return $old == $new || $category == null;
        });

        Validator::extend('alpha_spaces', function($attribute, $value)
        {
            return preg_match('/^[\pL\s]+$/u', $value);
        });
        $messages = array(
            'required'              => 'El nombre de la categoria es requerido',
            'new_category'          => 'Nombre existente, elige otro.',
            'alpha_spaces'          => 'Solo se permiten letras y espacios.'
        );
        $old = $request -> category_name;
        $new = $request -> new_category_name;
        $validator = Validator::make($request->all(), [
            'new_category_name' => "required|new_category:$old,$new|alpha_spaces",
        ], $messages);
        if ($validator->passes()) {
            $category = Category::where('name', '=', $request -> category_name)->first();
            $category->update(['name' => $request->new_category_name]);
            $category->save();
            if($old != $new)
                Storage::move('public/' . $request->category_name, 'public/' . $category->name);
            return response()->json(['success'=>'OK.']);
        }
        return response()->json(['error'=>$validator->errors()->all()]);
    }

    public function editTopic(Request $request){
        $old_topic_name         = $request -> topic_name;
        $new_topic_name         = $request -> new_topic_name;
        $topic                  = Topic::where('name','=', $old_topic_name) -> first();
        $old_category           = Category::where('id', '=', $topic -> category_id) -> first();
        $category               = Category::where('name', '=', $request -> new_category_name) -> first();

        Validator::extend('alpha_spaces', function($attribute, $value)
        {
            return preg_match('/^[\pL\s]+$/u', $value);
        });

        Validator::extend('not_default', function($field,$value,$parameters){
            return $value != 'Selecciona la categoria';
        });

        Validator::extend('valid_new_topic', function($field, $value, $parameters, $validator){
            $new_topic_name = $parameters[0];
            $old_topic_name = $parameters[1];
            $topic = Topic::where('name', '=',  $new_topic_name) -> first();
            return $new_topic_name == $old_topic_name || $topic == null;
        });

        $messages = array(
            'required'              => 'El nombre del tema es requerido.',
            'not_default'           => 'Selecciona una categoria.',
            'alpha_spaces'          => 'Solo se permiten letras y espacios.',
            'valid_new_topic'       => 'El nombre del tema ya existe, elige otro.'
        );

        $validator = Validator::make($request->all(), [
            'new_topic_name' => "required|alpha_spaces|valid_new_topic:$new_topic_name,$old_topic_name",
            'new_category_name' => 'not_default',
        ], $messages);

        if ($validator->passes()) {
            $topic -> update(['category_id'   => $category -> id, 'name' => $request -> new_topic_name]);
            $tags = $topic -> tags() -> get();
            for($i = 0; $i < count($tags); $i++){
                $topic -> tags() -> detach($tags[$i] -> id);
            }
            if($request -> tags != null){
                for($i = 0; $i < count($request -> tags); $i++){
                    $tag = Tag::firstOrNew(array(
                        'name'          => $request -> tags[$i],
                    ));
                    $tag -> save();
                    $topic -> tags() -> attach($tag -> id, [
                        'topic_id' => $topic -> id,
                        'category_id' => $topic -> category_id
                    ]);
                }
            }
            if($old_topic_name != $topic -> name && $old_category -> name != $category -> name)
                Storage::move('public/' . $old_category->name . '/' . $old_topic_name, 'public/' . $category->name . '/' . $topic->name);
            if($old_topic_name != $topic -> name && $old_category -> name == $category -> name)
                Storage::move('public/' . $old_category->name . '/' . $old_topic_name, 'public/' . $old_category->name . '/' . $topic->name);
            if($old_topic_name == $topic -> name && $old_category -> name != $category -> name)
                Storage::move('public/' . $old_category->name . '/' . $old_topic_name, 'public/' . $category->name . '/' . $old_topic_name);
            $topic -> save();
            return response() -> json(['success' => 'OK']);
        }

        return response()->json(['error'=>$validator->errors()->all()]);
    }

    public function displayTopic($name){
        $topic_name = $name;
        $urls       = [];
        $topic = Topic::where('name', '=', $topic_name) -> first();
        $R = Reference::where('topic_id', '=', $topic ->id) -> get();
        $references = [];
        $references['T'] = false;
        $references['C'] = false;
        $references['S'] = false;
        for($i = 0; $i < count($R); $i++){
            if($R[$i] -> type == 'C')
                $references['C'] = true;
            if($R[$i] -> type == 'T')
                $references['T'] = true;
            if($R[$i] -> type == 'S')
                $references['S'] = true;
        }
        return view('topic', compact(['topic_name', 'references']));
    }

    public function topicTheoryManager($name){
        return view('theorymanager', compact('name'));
    }

    public function topicSimulationManager($name){
        return view('simulationmanager', compact('name'));
    }

    public function topicQuestionnaireManager($name){
        return view('questionnairemanager', compact('name'));
    }

    public function registerTheoryFile(Request $request){
        if ($request->hasFile('input_file')) {
            $topic = Topic::where('name', '=', $request -> topic_name) -> first();
            $category   = Category::where('id', '=', $topic -> category_id) -> first();
            $name = $request -> file('input_file') -> getClientOriginalName();
            $destinationPath = public_path('storage/'.$category -> name.'/'.$topic -> name.'/Teoria/');
            $request -> input_file -> move($destinationPath, $name);
            $route = new Reference();
            $route -> type = 'T';
            $route -> route = $destinationPath;
            $route -> category_id = $category -> id;
            $route -> topic_id = $topic -> id;
            $route -> save();
            return redirect('creator/topic/'.$topic->name);
        }
    }

    public function registerSimulationFile(Request $request){
        $topic = Topic::where('name', '=', $request -> topic_name) -> first();
        $category   = Category::where('id', '=', $topic -> category_id) -> first();
        $file = $request -> file('input_file');
        $name = $request -> file('input_file')->getClientOriginalName();
        $destinationPath = public_path('storage/'.$category -> name.'/'.$topic -> name.'/Simulacion/');
        $request -> input_file -> move($destinationPath, 'archivo.zip');
        $zip = new ZipArchive();
        $zip_reference = $zip->open($destinationPath.'archivo.zip');
        if ($zip_reference){
            $zip->extractTo($destinationPath); // change this to the correct site path
            $zip->close();
            unlink($destinationPath.'archivo.zip');
            $reference = new Reference();
            $reference -> type = 'S';
            $reference -> route = $destinationPath;
            $reference -> category_id = $category -> id;
            $reference -> topic_id = $topic -> id;
            $reference -> save();
        }
        return redirect('creator/topic/'.$topic->name);
    }

    public function registerQuestionnaireFile(Request $request){
        if ($request->hasFile('input_file')) {
            $topic = Topic::where('name', '=', $request -> topic_name) -> first();
            $category   = Category::where('id', '=', $topic -> category_id) -> first();
            $file = $request -> file('input_file');
            $name = $request -> file('input_file')->getClientOriginalName();
            $destinationPath = public_path('storage/'.$category -> name.'/'.$topic -> name.'/Cuestionario/');
            $request -> input_file -> move($destinationPath, 'cuestionario.xml');
            $route = new Reference();
            $route -> type = 'C';
            $route -> route = $destinationPath;
            $route -> category_id = $category -> id;
            $route -> topic_id = $topic -> id;
            $route -> save();
            return redirect('creator/topic/'.$topic->name);
        }
    }

    public function registerTheoryManually(Request $request){
        $topic_name = $request -> topic_name;
        return view('register_theory_manually', compact('topic_name'));
    }

    public function registerQuestionnaireManually(Request $request){
        $topic_name = $request -> topic_name;
        $topic = Topic::where('name', '=', $topic_name) -> first();
        $category = Category::where('id', '=', $topic -> category_id) -> first();
        $category_name = $category -> name;
        return view('register_questionnaire_manually', compact(['topic_name', 'category_name']));
    }

    public function saveTheoryManually(Request $request){
        $topic = Topic::where('name', '=', $request -> topic_name) -> first();
        $category = Category::where('id', '=', $topic -> category_id) -> first();
        $destination_path = public_path('storage/'.$category -> name.'/'.$topic -> name.'/Teoria/teoria.xml');
        Storage::disk('local')->put('public/'.$category->name.'/'.$topic->name.'/Teoria/teoria.xml', $request -> xmlContent);
        $reference = new Reference();
        $reference -> type = 'T';
        $reference -> route = $destination_path;
        $reference -> category_id = $category -> id;
        $reference -> topic_id = $topic -> id;
        $reference -> save();
        return response() -> json(['success' => $destination_path.' '.$request->xmlContent]);
    }

    public function saveQuestionnaireManually(Request $request){
        $topic = Topic::where('name', '=', $request -> topic_name) -> first();
        $category = Category::where('id', '=', $topic -> category_id) -> first();
        $destination_path = public_path('storage/'.$category -> name.'/'.$topic -> name.'/Cuestionario/cuestionario.xml');
        Storage::disk('local')->put('public/'.$category->name.'/'.$topic->name.'/Cuestionario/cuestionario.xml', $request -> xmlContent);
        $reference = new Reference();
        $reference -> type = 'C';
        $reference -> route = $destination_path;
        $reference -> category_id = $category -> id;
        $reference -> topic_id = $topic -> id;
        $reference -> save();
        return response() -> json(['success' => 'OK']);
    }

}