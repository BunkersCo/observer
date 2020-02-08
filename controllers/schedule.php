<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

class Schedule extends OBFController
{

  public function __construct()
  {
    parent::__construct();

    $this->SchedulesModel = $this->load->model('Schedules');
    $this->SchedulesPermissionsModel = $this->load->model('SchedulesPermissions');
  }

  // return a friendly schedule for public display
  public function friendly_schedule()
  {

    $start = $this->data('start');
    $end = $this->data('end');
    $device = $this->data('device');

    $schedule = $this->SchedulesModel('friendly_schedule');

    if(!$schedule) return array(false,'Error getting schedule data.');

    return array(true,'Schedule data.',$schedule);

  }

  // get an individual scheduled show
  public function get_show()
  {

    $this->user->require_authenticated();

    $id = $this->data('id');

    $show = $this->SchedulesModel('get_show_by_id',$id,false);

    //T Schedule
    if(!$show) return array(false, ['Schedule','Show Not Found']);

    return array(true,'Scheduled show.',$show);

  }

  // get an individual recurring scheduled show
  public function get_show_recurring()
  {

    $this->user->require_authenticated();

    $id = $this->data('id');

    $show = $this->SchedulesModel('get_show_by_id',$id,true);

    //T Schedule
    //T Show not found.
    if(!$show) return array(false, ['Schedule','Show not found.']);

    return array(true,'Scheduled show (recurring).',$show);

  }

  // get an individual permission.
  public function get_permission()
  {

    $id = $this->data('id');

    $permission = $this->SchedulesPermissionsModel('get_permission_by_id',$id,false);

    $this->user->require_permission('manage_schedule_permissions:'.$permission['device_id']);

    //T Schedule
    //T Permission not found.
    if(!$permission) return array(false, ['Schedule','Permission not found.']);
    else return array(true,'Schedule permission.',$permission);

  }

  // get an individual recurring permission.
  public function get_permission_recurring()
  {

    $id = $this->data('id');

    $permission = $this->SchedulesPermissionsModel('get_permission_by_id',$id,true);

    $this->user->require_permission('manage_schedule_permissions:'.$permission['device_id']);

    //T Schedule
    //T Permission not found.
    if(!$permission) return array(false, ['Schedule','Permission Not Found']);
    else return array(true,'Schedule permission (recurring).',$permission);

  }

  // get shows between two given dates/times.
  // this function is used for the UI/API, but also to detect potential scheduling collisions.
  public function shows()
  {

    $this->user->require_authenticated();

    $start = $this->data('start');
    $end = $this->data('end');
    $device = $this->data('device');

    //T Schedule
    //T Player ID is invalid.
    if(!preg_match('/^[0-9]+$/',$device)) return array(false, ['Schedule','Player ID is invalid.']);
    if(!preg_match('/^[0-9]+$/',$start) || !preg_match('/^[0-9]+$/',$end)) return array(false, ['Schedule','Start Or End Date Invalid']);
    //T Schedule
    //T The start or end date is invalid.
    if($start>=$end) return array(false, ['Schedule','The start of end date is invalid.']);

    // check if device is valid.
    $this->db->where('id',$device);
    $device_data = $this->db->get_one('devices');

    //T Schedule
    //T Player ID is invalid.
    if(!$device_data) return array(false, ['Schedule','Player ID is invalid.']);

    $data = $this->SchedulesModel('get_shows',$start,$end,$device);

    return array(true,'Schedule data',$data);
  }

  public function shows_set_last_device()
  {
    $device_id = $this->data('device');

    $this->db->where('id',$device_id);
    $device_data = $this->db->get_one('devices');

    if($device_data)
    {
      $this->user->set_setting('last_schedule_device',$device_id);
      return array(true,'Set last schedule device.');
    }
    else return array(false,'Device not found.');
  }

  public function shows_get_last_device()
  {
    $device_id = $this->user->get_setting('last_schedule_device');
    if($device_id) return array(true,'Last schedule device.',$device_id);
    else return array(false,'Last schedule device not found.');
  }

  // get schedule permissions data between two given date/times.
  // also used to get timeslot data for own user.
  // this function is used for the UIAPI, but also to detect potential scheduling collisions
  public function permissions()
  {

    $this->user->require_authenticated();

    $start = $this->data('start');
    $end = $this->data('end');
    $device = $this->data('device');
    $user_id = $this->data('user_id');

    //T Schedule
    //T Player ID is invalid.
    if(!preg_match('/^[0-9]+$/',$device)) return array(false, ['Schedule','Player ID is invalid.']);

    // require "manage schedule permissions" permission unless we are getting the permissions for our own user.
    if($user_id!=$this->user->param('id')) $this->user->require_permission('manage_schedule_permissions:'.$device);

    //T Schedule
    //T Player ID is invalid.
    if(!preg_match('/^[0-9]+$/',$device)) return array(false, ['Schedule','Player ID is invalid.']);
    //T Schedule
    //T The start or end date is invalid.
    if(!preg_match('/^[0-9]+$/',$start) || !preg_match('/^[0-9]+$/',$end)) return array(false, ['Schedule','Start or end date is invalid.']);
    //T Schedule
    //T User ID is invalid.
    if(!empty($user_id) && !preg_match('/^[0-9]+$/',$user_id)) return array(false, ['Schedule','User ID is invalid.']);
    //T Schedule
    //T Start or end date is invalid.
    if($start>=$end) return array(false, ['Schedule','Start or end date is invalid.']);

    // check if device is valid.
    $this->db->where('id',$device);
    $device_data = $this->db->get_one('devices');

    //T Schedule
    // Device ID is invalid.
    if(!$device_data) return array(false, ['Schedule','Device ID is invalid.']);

    $data = $this->SchedulesPermissionsModel('get_permissions',$start,$end,$device,false,$user_id);

    return array(true,'Schedule permissions data',$data);

  }

  public function permissions_set_last_device()
  {
    $device_id = $this->data('device');

    $this->db->where('id',$device_id);
    $device_data = $this->db->get_one('devices');

    if($device_data)
    {
      $this->user->set_setting('last_schedule_permissions_device',$device_id);
      return array(true,'Set last schedule permissions device.');
    }
    else return array(false,'Device not found.');
  }

  public function permissions_get_last_device()
  {
    $device_id = $this->user->get_setting('last_schedule_permissions_device');
    if($device_id) return array(true,'Last schedule permissions device.',$device_id);
    else return array(false,'Last schedule permissions device not found.');
  }

  public function delete_permission()
  {

    $id = trim($this->data('id'));
    $recurring = trim($this->data('recurring'));

    // make sure permission exists, check user permissions against device ID.
    if($recurring) $permission = $this->SchedulesPermissionsModel('get_permission_by_id',$id,true);
    else $permission = $this->SchedulesPermissionsModel('get_permission_by_id',$id,false);
    //T Permission not found.
    if(!$permission) return array(false, ['Schedule','Permission not found.']);
    $this->user->require_permission('manage_schedule_permissions:'.$permission['device_id']);

    $this->SchedulesPermissionsModel('delete_permission',$id,$recurring);

    return array(true,'Permission deleted.');

  }

  public function delete_show()
  {

    $this->user->require_authenticated();

    $id = trim($this->data('id'));
    $recurring = trim($this->data('recurring'));

    // check permission.  we can delete a show that we have scheduled, regardless of whether we have permission on the timeslot anymore.
    $show = $this->SchedulesModel('get_show_by_id',$id,$recurring);
    if($show['user_id']!=$this->user->param('id')) $this->user->require_permission('manage_schedule_permissions');

    $this->SchedulesModel('delete_show',$id,$recurring);

    return array(true,'Show deleted.');

  }

  public function save_show()
  {

    $this->user->require_authenticated();

    $id=trim($this->data('id'));
    $edit_recurring = trim($this->data('edit_recurring'));

    $data['device_id']=trim($this->data('device_id'));
    $data['mode']=trim($this->data('mode'));
    $data['x_data']=trim($this->data('x_data'));

    $data['start']=trim($this->data('start'));

    $data['duration_days']=trim($this->data('duration_days'));
    $data['duration_hours']=trim($this->data('duration_hours'));
    $data['duration_minutes']=trim($this->data('duration_minutes'));
    $data['duration_seconds']=trim($this->data('duration_seconds'));

    $data['stop']=trim($this->data('stop'));

    $data['item_type']=trim($this->data('item_type'));
    $data['item_id']=trim($this->data('item_id'));

    // if we are editing, make sure ID is valid.
    if(!empty($id))
    {
      $original_show_data = $this->SchedulesModel('get_show_by_id',$id,$edit_recurring);
      //T Show not found.
      if(!$original_show_data) return array(false,['Schedule Edit','Show not found.']);

      // if we're editing someone elses show, we need to be a schedule admin.
      if($original_show_data['user_id']!=$this->user->param('id')) $this->user->require_permission('manage_schedule_permissions');
    }

    // validate show
    $validate = $this->SchedulesModel('validate_show',$data,$id);
    if($validate[0]==false) return array(false, ['Schedule Edit',$validate[1]]);

    // generate our duration in seconds.
    $duration = 0;
    $duration += $data['duration_seconds'];
    $duration += 60 * $data['duration_minutes'];
    $duration += 60 * 60 * $data['duration_hours'];
    $duration += 60 * 60 * 24 * $data['duration_days'];

    //T The duration is not valid.
    if(empty($duration)) return array(false, ['Schedule Edit','The duration is not valid.']);

    $data['duration']=$duration;

    // collision check!
    $collision_permission_check = $this->SchedulesModel('collision_permission_check',$data,$id,$edit_recurring);
    if($collision_permission_check[0]==false) return array(false, ['Schedule Edit',$collision_permission_check[1]]);

    // FINALLY CREATE/EDIT SHOW
    $this->SchedulesModel('save_show',$data,$id,$edit_recurring);

    return array(true,'Show added.');
  }

  public function save_permission()
  {

    $id=trim($this->data('id'));
    $edit_recurring = trim($this->data('edit_recurring'));

    $data['user_id']=trim($this->data('user_id'));
    $data['device_id']=trim($this->data('device_id'));
    $data['mode']=trim($this->data('mode'));
    $data['x_data']=trim($this->data('x_data'));

    $data['description']=trim($this->data('description'));

    $data['start']=trim($this->data('start'));

    $data['duration_days']=trim($this->data('duration_days'));
    $data['duration_hours']=trim($this->data('duration_hours'));
    $data['duration_minutes']=trim($this->data('duration_minutes'));
    $data['duration_seconds']=trim($this->data('duration_seconds'));

    $data['stop']=trim($this->data('stop'));

    // if we are editing, make sure ID is valid.
    if(!empty($id))
    {
      $original_permission = $this->SchedulesPermissionsModel('get_permission_by_id',$id,$edit_recurring);
      //T Permission not found.
      if(!$original_permission) return array(false, ['Schedule Edit','Permission not found.']);
    }

    $validate = $this->SchedulesPermissionsModel('validate_permission',$data,$id);
    if($validate[0]==false) return array(false, ['Schedule Edit',$validate[1]]);

    // make sure we have permission to save for this device.
    $this->user->require_permission('manage_schedule_permissions:'.$data['device_id']);

    // generate our duration in seconds.
    $duration = 0;
    $duration += $data['duration_seconds'];
    $duration += 60 * $data['duration_minutes'];
    $duration += 60 * 60 * $data['duration_hours'];
    $duration += 60 * 60 * 24 * $data['duration_days'];

    //T The duration is not valid.
    if(empty($duration)) return array(false,['Schedule Edit','The duration is not valid.']);

    $data['duration']=$duration;

    // collision check!
    $collision_check = $this->SchedulesPermissionsModel('collision_check',$data,$id,$edit_recurring);
    if($collision_check[0]==false) return array(false,['Schedule Edit',$collision_check[1]]);

    // FINALLY CREATE/EDIT PERMISSION
    $this->SchedulesPermissionsModel('save_permission',$data,$id,$edit_recurring);

    return array(true,'Permission added.');

  }

}
