<?php

namespace app\controllers;

use app\models\Paymant;
use Yii;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\PayHistory;
use app\models\WidgetSendedEmail;
use app\models\WidgetPendingCalls;
use app\models\WidgetSettings;
use app\models\WidgetActionMarks;

class ProfileController extends Controller
{
    public $publicActions = [];

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'pay', 'pay-history', 'analytics', 'savesiteimage', 'widgets', 'history', 'add-widget', 'update-widget', 'get-widget-code', 'pay-with', 'paid', 'fail','paid-ik', 'update-paid-ik'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        /*if(!Yii::$app->user->isGuest) {
            $this->layout = "@app/views/layouts/profile";
            return true;
        } else {
            $this->layout = "@app/views/layouts/main";
        }
        if(!in_array($action->id, $this->publicActions)) return $this->redirect(Yii::$app->user->loginUrl);*/
        $this->enableCsrfValidation = false;
        $this->layout = "@app/views/layouts/profile";
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionAnalytics()
    {
        return $this->render('analytics');
    }

    public function actionPay()
    {
        return $this->render('pay');
    }

    public function actionPaid()
    {
        return $this->render('paid');
    }
    public function actionPaidIk()
    {
        return $this->render('paid-ik');
    }

    public function actionFail()
    {
        return $this->render('fail');
    }

    public function actionPayHistory()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => PayHistory::find()->where('user_id='.Yii::$app->user->identity->id),
        ]);
        $dataProvider->setSort([
            'defaultOrder' => ['dateFormat' => SORT_DESC],
            'attributes' => [
                'id',
                'dateFormat' => [
                    'asc' => ['pay_history.date' => SORT_ASC],
                    'desc' => ['pay_history.date' => SORT_DESC],
                ],
                'payment',
                'typeFormat' => [
                    'asc' => ['pay_history.type' => SORT_ASC],
                    'desc' => ['pay_history.type' => SORT_DESC],
                ],
                'payStatus' => [
                    'asc' => ['pay_history.status' => SORT_ASC],
                    'desc' => ['pay_history.status' => SORT_DESC],
                ],
            ]
        ]);
        return $this->render('pay-history', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionWidgets()
    {
        $result = WidgetSettings::find()->where('user_id='.Yii::$app->user->identity->id)->all();
        return $this->render('widgets',['widgets' => $result]);
    }

    public function actionHistory()
    {
        $messageProvider = new ActiveDataProvider([
            'query' => WidgetSendedEmail::find()->join('INNER JOIN','widget_settings','widget_settings.widget_id=widget_sended_email_messeg.widget_id')->where('widget_settings.user_id='.Yii::$app->user->identity->id),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        $callProvider = new ActiveDataProvider([
            'query' => WidgetPendingCalls::find()->join('INNER JOIN','widget_settings','widget_settings.widget_id=widget_pending_calls.widget_id')->where('widget_settings.user_id='.Yii::$app->user->identity->id),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        $callProvider->setSort([
            'defaultOrder' => ['call_time' => SORT_DESC],
            'attributes' => [
                'widget_id',
                'call_time',
                'phone',
                'EndSide' => [
                    'asc' => ['widget_pending_calls.end_side' => SORT_ASC],
                    'desc' => ['widget_pending_calls.end_side' => SORT_DESC],
                ],
                'waiting_period_A',
                'waiting_period_B',
                'call_back_cost',
            ]
        ]);

        return $this->render('history', [
            'callProvider' => $callProvider,
            'messageProvider' => $messageProvider
        ]);
    }

    public function actionAddWidget()
    {
        $model = new WidgetSettings();
        $marks = new WidgetActionMarks();
        $postArray = Yii::$app->request->post();
        if(!empty($postArray))
        {
//            print_r($postArray);
//            die();
            $address = ['http://', 'https://'];
            $url = str_replace($address, '', Yii::$app->request->post('widget_site_url'));
            $code = "user-".Yii::$app->user->identity->id."-url-".$url."-date-".time();
            $model->widget_key = md5($code);
            $model->widget_status = 1;
            $model->widget_site_url = $url;
            $mail = '';
            for($i=1; $i<=$postArray['count_emails']; $i++)
            {
                $index = 'widget_user_email_'.$i;
                $mail.=$postArray[$index].';';
            }
            $model->widget_user_email = $mail;
            $black_list = '';
            for($i=1; $i<=$postArray['count_black_list']; $i++)
            {
                $index = 'black_list_number_'.$i;
                $black_list.=$postArray[$index].';';
            }
            $model->black_list = $black_list;
            $top = empty($postArray['witget-button-top']) ? 'top:0%;' : $postArray['witget-button-top'];
            $left = empty($postArray['witget-button-left']) ? 'left:0%;' : $postArray['witget-button-left'];
            $topMob = empty($postArray['witget-button-top']) ? 'top:0%;' : $postArray['witget-button-top'];
            $leftMob = empty($postArray['witget-button-left']) ? 'left:0%;' : $postArray['witget-button-left'];
            $model->widget_position = $top.$left;
            $model->widget_position_mobile = $postArray['witget-button-top-mob'].Yii::$app->request->post('witget-button-left-mob');
            $model->widget_name = Yii::$app->request->post('widget_name');
            $model->widget_button_color = Yii::$app->request->post('widget_button_color');
            //$model->widget_work_time = '{"work-start-time":"'.Yii::$app->request->post('work-start-time').'","work-end-time":"'.Yii::$app->request->post('work-end-time').'"}';
            $model->widget_theme_color = Yii::$app->request->post('widget_theme_color');
            $model->widget_yandex_metrika = Yii::$app->request->post('widget_yandex_metrika');
            $model->widget_google_metrika = $_POST['WidgetSettings']['widget_google_metrika'];
            $phones = '';
            for($i=1; $i<=$postArray['count_phones']; $i++)
            {
                $index = 'widget_phone_number_'.$i;
                $phones.=$postArray[$index].';';
            }
            $model->widget_phone_numbers=$phones;
            $model->user_id = Yii::$app->user->identity->id;
            $work_time['monday']['start'] = $postArray['work-start-time-monday'];
            $work_time['monday']['end'] = $postArray['work-end-time-monday'];
            $work_time['tuesday']['start'] = $postArray['work-start-time-tuesday'];
            $work_time['tuesday']['end'] = $postArray['work-end-time-tuesday'];
            $work_time['wednesday']['start'] = $postArray['work-start-time-wednesday'];
            $work_time['wednesday']['end'] = $postArray['work-end-time-wednesday'];
            $work_time['thursday']['start'] = $postArray['work-start-time-thursday'];
            $work_time['thursday']['end'] = $postArray['work-end-time-thursday'];
            $work_time['friday']['start'] = $postArray['work-start-time-friday'];
            $work_time['friday']['end'] = $postArray['work-end-time-friday'];
            $work_time['saturday']['start'] = $postArray['work-start-time-saturday'];
            $work_time['saturday']['end'] = $postArray['work-end-time-saturday'];
            $work_time['sunday']['start'] = $postArray['work-start-time-sunday'];
            $work_time['sunday']['end'] = $postArray['work-end-time-sunday'];
            $model->widget_work_time = json_encode($work_time);
            $model->widget_GMT = Yii::$app->request->post('widget_GMT');
            $model->widget_sound = Yii::$app->request->post('widget_sound');
            (Yii::$app->request->post('hand_turn_on'))?$model->hand_turn_on = 1 : $model->hand_turn_on = 0;
            (Yii::$app->request->post('utp_turn_on'))?$model->utp_turn_on = 1 : $model->utp_turn_on = 0;
            $model->widget_utp_form_position = Yii::$app->request->post('widget-utp-form-top').Yii::$app->request->post('widget-utp-form-left');
            $model->utm_button_color = Yii::$app->request->post('utm-button-color');
            $model->utp_img_url = Yii::$app->request->post('utp-img-url');
            if($model->save()) {
                $this->renameFileScreen($model->widget_id, $url, 1);
                $marks->widget_id = $model->widget_id;
                $marks->other_page = $postArray['other_page'];
                $marks->scroll_down = $postArray['scroll_down'];
                $marks->active_more40 = $postArray['active_more40'];
                $marks->mouse_intencivity = $postArray['mouse_intencivity'];
                $marks->sitepage3_activity = $postArray['sitepage3_activity'];
                $marks->more_avgtime = $postArray['more_avgtime'];
                $marks->moretime_after1min = $postArray['moretime_after1min'];
                $marks->form_activity = $postArray['form_activity'];
                $marks->client_activity = $postArray['client_activity'];
                $sites = '';
                for($i=1; $i<=$postArray['count_pages']; $i++)
                {
                    $sites .= $postArray['site_page_'.$i].'*'.$postArray['select_site_page_'.$i].';';
                }
                $marks->site_pages_list = $sites;
                if($marks->save()) {
                    return $this->redirect(['profile/widgets']);
                } else {
                    print_r($marks->errors);
                    die();
                }
            } else {
                print_r($model->errors);
                die();
            }
        } else {
            return $this->render('add-widget', [
                'model' => $model,
                'marks' => $marks,
            ]);
        }
    }

    public function actionUpdateWidget($id)
    {
        $model = $this->findModel($id);
        $marks = $this->findMarks($id);

        $postArray = Yii::$app->request->post();
        if(!empty($postArray))
        {
            $address = ['http://', 'https://'];
            $url = str_replace($address, '', Yii::$app->request->post('widget_site_url'));
            $model->widget_status = 1;
            $model->widget_site_url = $url;
            $email = '';
            for($i=1; $i<=$postArray['count_emails']; $i++)
            {
                $index = 'widget_user_email_'.$i;
                $email.=$postArray[$index].';';
            }
            $model->widget_user_email = $email;
            $black_list = '';
            for($i=1; $i<=$postArray['count_black_list']; $i++)
            {
                $index = 'black_list_number_'.$i;
                $black_list.=$postArray[$index].';';
            }
            $model->black_list = $black_list;
            $model->widget_position = $postArray['witget-button-top'].Yii::$app->request->post('witget-button-left');
            $model->widget_position_mobile = $postArray['witget-button-top-mob'].Yii::$app->request->post('witget-button-left-mob');
            $model->widget_name = Yii::$app->request->post('widget_name');
            $model->widget_button_color = Yii::$app->request->post('widget_button_color');
            //$model->widget_work_time = '{"work-start-time":"'.Yii::$app->request->post('work-start-time').'","work-end-time":"'.Yii::$app->request->post('work-end-time').'"}';
            $model->widget_theme_color = Yii::$app->request->post('widget_theme_color');
            $model->widget_yandex_metrika = Yii::$app->request->post('widget_yandex_metrika');
            $model->widget_google_metrika = $_POST['WidgetSettings']['widget_google_metrika'];
            $phone = '';
            for($i=1; $i<=$postArray['count_phones']; $i++)
            {
                $index = 'widget_phone_number_'.$i;
                $phone.=$postArray[$index].';';
            }
            $model->widget_phone_numbers = $phone;
            $model->user_id = Yii::$app->user->identity->id;
            $model->widget_GMT = Yii::$app->request->post('widget_GMT');
            $work_time['monday']['start'] = $postArray['work-start-time-monday'];
            $work_time['monday']['end'] = $postArray['work-end-time-monday'];
            $work_time['tuesday']['start'] = $postArray['work-start-time-tuesday'];
            $work_time['tuesday']['end'] = $postArray['work-end-time-tuesday'];
            $work_time['wednesday']['start'] = $postArray['work-start-time-wednesday'];
            $work_time['wednesday']['end'] = $postArray['work-end-time-wednesday'];
            $work_time['thursday']['start'] = $postArray['work-start-time-thursday'];
            $work_time['thursday']['end'] = $postArray['work-end-time-thursday'];
            $work_time['friday']['start'] = $postArray['work-start-time-friday'];
            $work_time['friday']['end'] = $postArray['work-end-time-friday'];
            $work_time['saturday']['start'] = $postArray['work-start-time-saturday'];
            $work_time['saturday']['end'] = $postArray['work-end-time-saturday'];
            $work_time['sunday']['start'] = $postArray['work-start-time-sunday'];
            $work_time['sunday']['end'] = $postArray['work-end-time-sunday'];
            $model->widget_work_time = json_encode($work_time);
            $model->widget_sound = Yii::$app->request->post('widget_sound');
            (Yii::$app->request->post('hand_turn_on'))?$model->hand_turn_on = 1 : $model->hand_turn_on = 0;
            (Yii::$app->request->post('utp_turn_on'))?$model->utp_turn_on = 1 : $model->utp_turn_on = 0;
            $model->widget_utp_form_position = Yii::$app->request->post('widget-utp-form-top').Yii::$app->request->post('widget-utp-form-left');
            $model->utm_button_color = Yii::$app->request->post('utm-button-color');
            $model->utp_img_url = Yii::$app->request->post('utp-img-url');
            if($model->save()) {
                $this->renameFileScreen($model->widget_id, $url, 2);
                $marks->widget_id = $model->widget_id;
                $marks->other_page = $postArray['other_page'];
                $marks->scroll_down = $postArray['scroll_down'];
                $marks->active_more40 = $postArray['active_more40'];
                $marks->mouse_intencivity = $postArray['mouse_intencivity'];
                $marks->sitepage3_activity = $postArray['sitepage3_activity'];
                $marks->more_avgtime = $postArray['more_avgtime'];
                $marks->moretime_after1min = $postArray['moretime_after1min'];
                $marks->form_activity = $postArray['form_activity'];
                $marks->client_activity = $postArray['client_activity'];
                $sites = '';
                for($i=1; $i<=$postArray['count_pages']; $i++)
                {
                    $sites .= $postArray['site_page_'.$i].'*'.$postArray['select_site_page_'.$i].';';
                }
                $marks->site_pages_list = $sites;
                if($marks->save()) {
                    return $this->redirect(['profile/widgets']);
                } else {
                    print_r($marks->errors);
                    die();
                }
            } else {
                print_r($model->errors);
                die();
            }
        } else {
            return $this->render('update-widget', [
                'model' => $model,
                'marks' => $marks,
            ]);
        }
    }

    public function actionGetWidgetCode($code)
    {
     return '<textarea style="width: 100%; height: 370px;" readonly>
            <!-- Start script WidgetRobax -->
            <script type="text/javascript">
            (function (d, w) {
                var robax_widget="robax-"+"'.$code.'";
                var n = d.getElementsByTagName("script")[0],
                    s = d.createElement("script"),
                    c = function () {w["robax_widget"+robax_widget]=new RobaxWidget({id:robax_widget,key:"'.$code.'",w:w});},
                    f = function () {n.parentNode.insertBefore(s, n); d.getElementById(robax_widget).onload=c;};
                    s.id=robax_widget;
                    s.type = "text/javascript";
                    s.async = true;
                    s.src = "//r.oblax.ru/widget-front/robax.js";
                if (w.opera == "[object Opera]") {
                    d.addEventListener("DOMContentLoaded", f, false);
                } else { f(); }
            })(document, window);
            </script>
            <!-- End of script WidgetRobax -->
        </textarea>';
    }

    public function actionPayWith()
    {
        $model = new Paymant();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            return $this->render('pay-with');
        }
        else
        {
            return $this->render('pay-with');
        }
    }

    public function actionSavesiteimage()
    {
        if ($_POST) {
            $path = '/files/images/desktop/';
            $path2 = '/files/images/mobile/';
            if (!file_exists(Yii::getAlias('@webroot').$path)){
                mkdir(Yii::getAlias('@webroot').$path, 0777, true);
                file_put_contents(Yii::getAlias('@webroot').$path.$_POST['site'].'.jpg', file_get_contents($_POST['url_desktop']));
            } else {
                file_put_contents(Yii::getAlias('@webroot').$path.$_POST['site'].'.jpg', file_get_contents($_POST['url_desktop']));
            }
            if (!file_exists(Yii::getAlias('@webroot').$path2)){
                mkdir(Yii::getAlias('@webroot').$path2, 0777, true);
                file_put_contents(Yii::getAlias('@webroot').$path2.$_POST['site'].'.jpg', file_get_contents($_POST['url_mobile']));
            } else {
                file_put_contents(Yii::getAlias('@webroot').$path2.$_POST['site'].'.jpg', file_get_contents($_POST['url_mobile']));
            }
        }
    }

    protected function renameFileScreen($widgetId, $url, $action)
    {
        $path = '/files/images/desktop/';
        $path2 = '/files/images/mobile/';
        if (file_exists(Yii::getAlias('@webroot').$path.$url.'.jpg')){
            $new_path = $action == 1 ? Yii::getAlias('@webroot').$path.$url.'.jpg' : Yii::getAlias('@webroot').$path.$widgetId.'-'.$url.'.jpg';
            if (file_exists(Yii::getAlias('@webroot').$path.$widgetId.'-'.$url.'.jpg')) {
                rename($new_path, Yii::getAlias('@webroot') . $path . $widgetId . '-' . $url . '.jpg');
            } else {
                rename(Yii::getAlias('@webroot').$path.$url.'.jpg', Yii::getAlias('@webroot') . $path . $widgetId . '-' . $url . '.jpg');
            }
        }
        if (file_exists(Yii::getAlias('@webroot').$path2.$url.'.jpg')){
            $new_path2 = $action == 1 ? Yii::getAlias('@webroot').$path2.$url.'.jpg' : Yii::getAlias('@webroot').$path2.$widgetId.'-'.$url.'.jpg';
            if (file_exists(Yii::getAlias('@webroot').$path2.$widgetId.'-'.$url.'.jpg')) {
                rename($new_path2, Yii::getAlias('@webroot') . $path2 . $widgetId . '-' . $url . '.jpg');
            } else {
                rename(Yii::getAlias('@webroot').$path2.$url.'.jpg', Yii::getAlias('@webroot') . $path2 . $widgetId . '-' . $url . '.jpg');
            }
        }
    }

    protected function findModel($id)
    {
        if (($model = WidgetSettings::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    protected function findMarks($id)
    {
        if (($model = WidgetActionMarks::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionUpdatePaidIk()
    {
        return $this->render('paid-ik');
    }

}
