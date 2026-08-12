-- 修复admin用户的角色
-- 在phpMyAdmin中运行这个SQL来修复admin用户的role字段

USE warehouse_db;

-- 将username为'admin'的用户的role改为'admin'
UPDATE users SET role = 'admin' WHERE username = 'admin';

-- 查看结果
SELECT id, username, role FROM users WHERE username = 'admin';

