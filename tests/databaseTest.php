<?php
/**
 * ark.database
 * @copyright 2015 Liu Dong <ddliuhb@gmail.com>
 * @license MIT
 */

class DatabaseTest extends PHPUnit_Framework_TestCase{
    protected $db;
    
    protected function getSampleUser($key = 1){
        return array(
            'name' => 'user'.$key,
            'email' => 'user'.$key.'@example.com',
            'point' => $key
        );
    }
    
    protected function setup(){
        $this->db = new \Ark\Database\Connection('sqlite::memory:', '', '', array('prefix' => 'pre_'));
        $this->db->exec("
                CREATE TABLE IF NOT EXISTS pre_contact (
                    id INTEGER PRIMARY KEY, 
                    name TEXT, 
                    email TEXT,
                    point INTEGER DEFAULT 0
                )
             ");
    }
    
    public function testConnection(){
        //show table
        $query = $this->db->query("SELECT * FROM sqlite_master WHERE type='table'");
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($row['name'], 'pre_contact');
        
        //insert
        $rst = $this->db->exec("INSERT INTO pre_contact (name, email) VALUES ('test', 'test@test.com')");
        $this->assertEquals($rst, 1);
        
        //count
        $query = $this->db->query("SELECT COUNT(*) FROM pre_contact");
        $this->assertEquals($query->fetchColumn(), 1);
    }
    
    public function testBuilder(){
        $cmd = $this->db->builder();
        //insert
        $rst = $cmd->insert('{{contact}}', array(
            'name' => 'test',
            'email' => 'test@test.com',
        ));
        $this->assertEquals($rst, 1);
        
        $total = $cmd->reset()->select('COUNT(*)')->from('{{contact}}')->queryValue();
        $this->assertEquals($total, 1);
        
        //select
        $name = $cmd->reset()->select('name')->from('{{contact}}')->limit(1)->queryValue();
        $this->assertEquals($name, 'test');
        
        //update
        $rst = $cmd->reset()->update('{{contact}}', 
            array(
                'email' => 'newemail@test.com',
                'point = point + 1',
            ),
            'name=:name',
            array(
                ':name' => 'test',
            )
        );
        
        $this->assertEquals($rst, 1);
        
        //select
        $email = $cmd->reset()->select('email')->from('{{contact}}')->limit(1)->queryValue();
        $this->assertEquals($email, 'newemail@test.com');
        
        //delete
        $rst = $cmd->reset()->delete('{{contact}}', 'name=:name', array(
            ':name' => 'test',
        ));
        
        $this->assertEquals($rst, 1);
        
        //count
        $rst = $cmd->reset()->select('COUNT(*)')->from('{{contact}}')->queryValue();
        $this->assertEquals($rst, 0);
    }
    
    public function testFactory(){
        $factory = $this->db->factory('@{{contact}}');
        
        //insert
        $rst = $factory->insert($this->getSampleUser());
        $this->assertEquals($rst, 1);
        
        //count
        $total = $factory->count();
        $this->assertEquals($total, 1);
        
        //select
        $model = $factory->findOneByName('user1');
        $this->assertEquals($model->email, 'user1@example.com');
        
        //update
        $rst = $factory->updateByName('user1', array(
            'email' => 'user1@mail.example.com',
        ));
        $this->assertEquals($rst, 1);
        
        //select
        $model = $factory->findOneByName('user1');
        $this->assertEquals($model->email, 'user1@mail.example.com');
        
        //delete
        $rst = $factory->deleteByName('user1');
        $this->assertEquals($rst, 1);
        
        //count
        $total = $factory->count();
        $this->assertEquals($total, 0);
    }
    
    public function testModel(){
        $factory = $this->db->factory('@{{contact}}');
        
        //insert
        $model = $factory->create($this->getSampleUser());
        $rst = $model->save();
        $this->assertEquals($rst, 1);
        
        $fetchModel = $factory->find(1);
        $this->assertEquals($fetchModel->name, 'user1');
        
        //update
        $model->email = 'newemail@test.com';
        $rst = $model->save();
        $this->assertEquals($rst, 1);
        
        $fetchModel = $factory->findOneByName('user1');
        $this->assertEquals($fetchModel->email, 'newemail@test.com');
        
        //delete
        $rst = $model->delete();
        $this->assertEquals($rst, 1);
    }
    
    public function testTransaction(){
    }
}