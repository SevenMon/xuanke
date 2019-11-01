<?php
namespace app\teacherapi\controller;

use app\model\Course;
use think\Db;
use think\Exception;
use think\Request;

class Courses extends Base
{

    private $question_content = array(
        '1' => '1、对老师和同学的问候做出了语言或动作的回应',
        '2' => '2、主动与老师和同学相互问候',
        '3' => '3、在问候寒暄时正确使用了日期或一周中每天的英文名',
        '4' => '4、在问候寒暄时主动描述了自己或家人的活动',
        '5' => '5、在描述人事物时正确使用了第三人称单数、名词复数等语法',
        '6' => '6、用动作或适当反应表明是否听懂了老师的指令',
        '7' => '7、用单词或短语回答了老师提出的问题',
        '8' => '8、用简单句回答了老师的问题',
        '9' => '9、用简单句主动向老师或同学描述了人、物、地',
        '10' => '10、用学过的句型主动向老师提出了一个问题或请求',
        '11' => '11、用2-3个单词回答了有关个人及当下场景的问题',
        '12' => '12、在对话场景中主动使用了颜色、身体部位、数字、衣物等学过的名词',
        '13' => '13、在对话场景中主动使用了形容词修饰名词的结构描述人、事、物',
        '14' => '14、在回答问题时使用了方位介词指明人、物的位置',
        '15' => '15、在回答问题时使用了连词表示并列、因果等关系',
    );

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->checkLogin();
    }

    public function index(){
        $page = input('page',1);
        $limit = config('api_page_limit');

        $year = input('year',date('Y',time()));
        $month = input('month',date('m',time()));

        $begin_time = strtotime($year.'-'.$month.'-01');
        $end_time = strtotime("+1 month",strtotime($year.'-'.$month.'-01'));

        $where = array();
        $where['teacher_main_uid'] = $this->edu_teacher_info['uid'];
        $where['start_time'] = array('between',array($begin_time,$end_time));
        $where['status'] = 1;
        $course_all_num = Db::name('course')->where($where)->count();
        $course_list = Db::name('course')->where($where)->order('start_time desc')->page($page,$limit)->select();
        $course_model = new Course();
        foreach ($course_list as $key => $value){
            $course_list[$key] = $course_model->getDetail($value['id']);
        }

        $result['course_list'] = $course_list;
        $result['page'] = array(
            'all_num' => $course_all_num,
            'limit' => $limit,
            'current_page' => $page,
            'all_page' => ceil($course_all_num/$limit),
        );
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ));

    }

    //签到详情
    public function signInIndex()
    {
        $id = input('id','');
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }

        $where = array();
        $where['course_id'] = $course_info['id'];
        $where['status'] = array('in',array(1,2,4));
        $book_list = Db::name('book')->where($where)->order('create_at desc')->select();
        $edu_db = Db::connect(config('edu_database'));
        foreach ($book_list as $key => &$value){
            $student_info = Db::name('student')->where(array('id' => $value['student_id']))->find();
            $value['student'] = $student_info;
            $value['edu_student'] = $edu_db->name('student_baseinfo')->where(array('id'=>$student_info['edu_student_id']))->find();
        }
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'course_info' => $course_info,
                'book_list' => $book_list,
            )
        ));
    }

    public function signIn(){
        $id = input('id','');//课程id
        $student_id = input('student_id','');  //逗号分割
        $status = input('status','');
        if(empty($id) || empty($student_id) || empty($status)){
            return json(array(
                'status' => -1,
                'msg' => '参数不完整',
                'data' => array()
            ));
        }
        if(!in_array($status,[2,4])){
            return json(array(
                'status' => -1,
                'msg' => 'status错误',
                'data' => array()
            ));
        }
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }


        $student_id_str = explode(',',$student_id);
        $where = array();
        $where['course_id'] = $id;
        $where['status'] = array('in',array(1,2,4));
        $where['student_id'] = array('in',$student_id_str);
        try{
            Db::name('book')->where($where)->update(array('status' => $status));
            return json(array(
                'status' => 1,
                'msg' => '操作成功',
                'data' => array()
            ));
        }catch (Exception $e){
            return json(array(
                'status' => -1,
                'msg' => '操作失败',
                'data' => array()
            ));
        }

    }

    public function classMiddleIndex()
    {
        $id = input('id','');//课程id
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }
        $this->initAssess($id);
        $assess = Db::name('assess')->where(array('course_id' => $id))->find();
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'assess' => $assess
            )
        ));
    }

    public function classMiddleDetail()
    {
        $id = input('id','');//课程id
        $question_num = input('question_num',1);
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }

        $assess = Db::name('assess')->field('question'.$question_num)->where(array('course_id' => $id))->find();
        $question_detail = $assess['question'.$question_num] == -1 ? [] : explode(',',$assess['question'.$question_num]);
        $question_content = $this->question_content[$question_num];

        $where = array();
        $where['course_id'] = $course_info['id'];
        $where['status'] = 2;
        $book_list = Db::name('book')->where($where)->order('create_at desc')->select();
        $edu_db = Db::connect(config('edu_database'));
        foreach ($book_list as $key => &$value){
            $student_info = Db::name('student')->where(array('id' => $value['student_id']))->find();

            $value['student'] = $student_info;
            $value['student']['select'] = in_array($value['student_id'],$question_detail)?1:0;
            $value['edu_student'] = $edu_db->name('student_baseinfo')->where(array('id'=>$student_info['edu_student_id']))->find();
        }
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'question_content' => $question_content,
                'book_list' => $book_list,
                'course_info' => $course_info,
            )
        ));
    }

    public function classMiddleAssess()
    {
        $id = input('id','');//课程id
        $question_num = input('question_num','');
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }
        $student_id_str = input('student_id_str','');//学生id  逗号隔开
        try{
            Db::name('assess')->where(array('course_id' => $id))->update(array('question'.$question_num => $student_id_str));
            return json(array(
                'status' => 1,
                'msg' => '保存成功',
                'data' => array()
            ));
        }catch (Exception $e){
            return json(array(
                'status' => -1,
                'msg' => '保存失败',
                'data' => array()
            ));
        }
    }



    public function classAfterIndex()
    {
        $id = input('id','');//课程id
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }
        $this->initAssess($id);
        $assess = Db::name('assess')->where(array('course_id' => $id))->find();

        $word_pass_student = explode(',',$assess['word_pass']);
        $word_not_pass_student = explode(',',$assess['word_not_pass']);

        $where = array();
        $where['course_id'] = $course_info['id'];
        $where['status'] = 2;
        $book_list = Db::name('book')->where($where)->order('create_at desc')->select();
        $edu_db = Db::connect(config('edu_database'));
        foreach ($book_list as $key => &$value){
            $student_info = Db::name('student')->where(array('id' => $value['student_id']))->find();
            $value['student'] = $student_info;
            $value['student']['pass_or'] = in_array($value['student_id'],$word_pass_student)?1:(in_array($value['student_id'],$word_not_pass_student)?2:0);
            $value['edu_student'] = $edu_db->name('student_baseinfo')->where(array('id'=>$student_info['edu_student_id']))->find();
        }

        return json(array(
            'status' => 1,
            'msg' => '操作成功',
            'data' => array(
                'assess' => $assess,
                'book_list' => $book_list
            )
        ));
    }

    public function classAfterAssess()
    {
        $word1 = input('word1','');
        $word2 = input('word2','');
        $word3 = input('word3','');
        $main_word = input('main_word','');
        $word_pass_student_str = input('word_pass_student_str','');//逗号隔开
        $word_not_pass_student_str = input('word_not_pass_student_str','');//逗号隔开
        if(empty($word1) || empty($word2) || empty($word3) || empty($main_word)){
            return json(array(
                'status' => -1,
                'msg' => '参数不完整',
                'data' => array()
            ));
        }

        $id = input('id','');//课程id
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }

        $data = array(
            'word1' => $word1,
            'word2' => $word2,
            'word3' => $word3,
            'main_word' => $main_word,
            'word_pass' => $word_pass_student_str,
            'word_not_pass' => $word_not_pass_student_str,
        );

        cache('course_after_assess_info_'.$id,$data);
        //Db::name('assess')->where(array('course_id' => $id))->update($data);
        return json(array(
            'status' => 1,
            'msg' => '保存成功',
            'data' => array()
        ));
    }

    private function initAssess($id)
    {
        $assess = Db::name('assess')->where(array('course_id' => $id))->find();
        if($assess == null){
            $info = Db::name('assess')->insert(array('course_id' => $id));
            return $info;
        }
    }

    public function showAssess(){
        $id = input('id','');//课程id
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }
        $assess = Db::name('assess')->where(array('course_id' => $id))->find();
        $assess = array_merge($assess,cache('course_after_assess_info_'.$id));
        if($assess['question1'] == -1
            || $assess['question2'] == -1
            || $assess['question3'] == -1
            || $assess['question4'] == -1
            || $assess['question5'] == -1
            || $assess['question6'] == -1
            || $assess['question7'] == -1
            || $assess['question8'] == -1
            || $assess['question9'] == -1
            || $assess['question10'] == -1
            || $assess['question11'] == -1
            || $assess['question12'] == -1
            || $assess['question13'] == -1
            || $assess['question14'] == -1
            || $assess['question15'] == -1
            || empty($assess['word1'])
            || empty($assess['word2'])
            || empty($assess['word3'])
            || empty($assess['main_word'])
        ){
            return json(array(
                'status' => -2,
                'msg' => '请完善测评之后再阅览测评',
                'data' => array()
            ));
        }


        $word_pass_student = explode(',',$assess['word_pass']);
        $word_not_pass_student = explode(',',$assess['word_not_pass']);

        $where = array();
        $where['course_id'] = $course_info['id'];
        $where['status'] = 2;
        $book_list = Db::name('book')->where($where)->order('create_at desc')->select();
        $edu_db = Db::connect(config('edu_database'));
        foreach ($book_list as $key => &$value){
            $student_info = Db::name('student')->where(array('id' => $value['student_id']))->find();
            $value['student'] = $student_info;
            $value['student']['pass_or'] = in_array($value['student_id'],$word_pass_student)?1:(in_array($value['student_id'],$word_not_pass_student)?2:0);
            $value['edu_student'] = $edu_db->name('student_baseinfo')->where(array('id'=>$student_info['edu_student_id']))->find();
            $temp_count = 0;
            for($i=1;$i<=15;$i++){
                if(in_array($value['student_id'],explode(',',$assess['question'.$i]))){
                    $temp_count++;
                }
            }
            if($temp_count >=1 && $temp_count <=3){
                $before_assess = 'D';
            }elseif ($temp_count >=4 && $temp_count <=7){
                $before_assess = 'C';
            }elseif ($temp_count >=8 && $temp_count <=11){
                $before_assess = 'B';
            }elseif ($temp_count >=12){
                $before_assess = 'A';
            }else{
                $before_assess = '无评分';
            }
            $value['student']['before_assess'] = $before_assess;
        }

        return json(array(
            'status' => 1,
            'msg' => '操作成功',
            'data' => array(
                'assess' => $assess,
                'book_list' => $book_list,
            )
        ));
    }

    public function saveAssess(){
        $id = input('id','');//课程id
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        if($course_info['teacher_main_uid'] != $this->edu_teacher_info['uid']){
            return json(array(
                'status' => -1,
                'msg' => '该课程，您不能查看',
                'data' => array()
            ));
        }
        $assess = Db::name('assess')->where(array('course_id' => $id))->find();
        $assess = array_merge($assess,cache('course_after_assess_info_'.$id));
        if($assess['question1'] == -1
            || $assess['question2'] == -1
            || $assess['question3'] == -1
            || $assess['question4'] == -1
            || $assess['question5'] == -1
            || $assess['question6'] == -1
            || $assess['question7'] == -1
            || $assess['question8'] == -1
            || $assess['question9'] == -1
            || $assess['question10'] == -1
            || $assess['question11'] == -1
            || $assess['question12'] == -1
            || $assess['question13'] == -1
            || $assess['question14'] == -1
            || $assess['question15'] == -1
            || empty($assess['word1'])
            || empty($assess['word2'])
            || empty($assess['word3'])
            || empty($assess['main_word'])
        ){
            return json(array(
                'status' => -1,
                'msg' => '请完善测评之后再阅览测评',
                'data' => array()
            ));
        }

        try{
            Db::name('assess')->where(array('course_id' => $id))->update($assess);
            cache('course_after_assess_info_'.$id,array());
            return json(array(
                'status' => 1,
                'msg' => '保存成功',
                'data' => array()
            ));
        }catch (Exception $e){
            return json(array(
                'status' => -1,
                'msg' => '保存失败',
                'data' => array()
            ));
        }
    }

}
