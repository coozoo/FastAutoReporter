-- phpMyAdmin SQL Dump
-- version 5.1.1-1.fc34
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 22, 2021 at 01:58 PM
-- Server version: 10.5.12-MariaDB
-- PHP Version: 7.4.26

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `reporter`
--
CREATE DATABASE IF NOT EXISTS `reporter` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `reporter`;

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `add_run`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_run` (IN `buildVersion` LONGTEXT, IN `environment` VARCHAR(255), IN `runName` VARCHAR(255), IN `runUid` VARCHAR(255), IN `testTeam` VARCHAR(255), IN `testType` VARCHAR(255), IN `isDevelopementRun` BOOL)  BEGIN
	DECLARE environment_id BIGINT;
    DECLARE testTeam_id BIGINT;
    DECLARE testType_id BIGINT;
    DECLARE run_id BIGINT;
	DECLARE errno INT;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		GET CURRENT DIAGNOSTICS CONDITION 1 errno = MYSQL_ERRNO;
		SELECT errno AS MYSQL_ERROR;
		ROLLBACK;
	END;

	SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED ;
	START TRANSACTION;
    
    
    -- put creations into separate procedures if you need it somwhere else
    
    -- check if such env exists if no then add
	SET environment_id = (select `id` from `environment` where `e_env_name`=environment);
    -- add new environment 
	IF environment_id is null THEN
		INSERT INTO `environment` (`e_env_name`)
		VALUES(environment);
		SET environment_id=(SELECT LAST_INSERT_ID());
	END IF;
    
	-- check if such testeam exists if no then add
	SET testTeam_id = (select `id` from `team` where `tm_team_name`=testTeam);
    -- add new testteam 
	IF testTeam_id is null THEN
		INSERT INTO `team` (`tm_team_name`)
		VALUES(testTeam);
		SET testTeam_id=(SELECT LAST_INSERT_ID());
	END IF;
    
	-- check if such testtype exists if no then add
	SET testType_id = (select `id` from `testtype` where `tt_test_type_name`=testType);
    -- add new testtype 
	IF testType_id is null THEN
		INSERT INTO `testtype` (`tt_test_type_name`)
		VALUES(testType);
		SET testType_id=(SELECT LAST_INSERT_ID());
	END IF;

    set run_id=(select id from run where r_run_uid=runUid);
	IF run_id is null THEN
		insert into run (r_build_version, environment_id, r_is_developement_run, r_is_run_finished, r_run_name, r_run_uid, team_id, test_type_id) 
		values (buildVersion, environment_id, isDevelopementRun, 0, runName, runUid, testTeam_id, testType_id);
		SET run_id=(SELECT LAST_INSERT_ID());
    END IF;
    select environment_id,testTeam_id,testType_id,run_id;
	
    COMMIT WORK;
END$$

DROP PROCEDURE IF EXISTS `add_suite`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_suite` (IN `runUid` VARCHAR(255), IN `suiteName` VARCHAR(255), IN `suiteUid` VARCHAR(255))  BEGIN
	DECLARE suite_id BIGINT;
    DECLARE run_id BIGINT;
	DECLARE errno INT;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		GET CURRENT DIAGNOSTICS CONDITION 1 errno = MYSQL_ERRNO;
		SELECT errno AS MYSQL_ERROR;
		ROLLBACK;
	END;
    
	SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED ;
	START TRANSACTION;
    

	set run_id=(select id from run where r_run_uid=runUid);
    set suite_id=(select id from suite where s_suite_uid=suiteUid);
	IF suite_id is null THEN
		insert into suite (s_suite_name, s_suite_uid, s_run_id)
        values (suiteName, suiteUid, run_id);
		SET suite_id=(SELECT LAST_INSERT_ID());
    END IF;
    
	select run_id,suite_id;
    
    COMMIT WORK;
END$$

DROP PROCEDURE IF EXISTS `add_test`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_test` (IN `additionalInfo` LONGTEXT, IN `defect` VARCHAR(255), IN `feature` VARCHAR(255), IN `jiraId` VARCHAR(255), IN `suiteUid` VARCHAR(255), IN `testAuthor` VARCHAR(255), IN `testDuration` BIGINT, IN `testFinishDate` VARCHAR(255), IN `testName` VARCHAR(255), IN `testResult` VARCHAR(255), IN `testStartDate` VARCHAR(255), IN `testUid` VARCHAR(255), IN `testVideoFileName` VARCHAR(255), IN `testrailId` VARCHAR(255))  BEGIN
	DECLARE feature_id BIGINT;
	DECLARE testResult_id BIGINT;
	DECLARE testAuthor_id BIGINT;
	DECLARE test_id BIGINT;
	DECLARE run_id BIGINT;
	DECLARE suite_id BIGINT;
	DECLARE testFinishDate_date DATETIME;
	DECLARE testStartDate_date DATETIME;
	DECLARE errno INT;
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
		GET CURRENT DIAGNOSTICS CONDITION 1 errno = MYSQL_ERRNO;
		SELECT errno AS MYSQL_ERROR;
		ROLLBACK;
	END;

	SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED ;
	START TRANSACTION;
	
		SET testFinishDate_date=STR_TO_DATE(testFinishDate,'%Y-%m-%dT%T.%f');
		SET testStartDate_date=STR_TO_DATE(testStartDate,'%Y-%m-%dT%T.%f');  

		-- put creations into separate procedures if you need it somwhere else

		-- check if such feature exists if no then add
		SET feature_id = (select `id` from `feature` where `f_feature_name`=feature);
		-- add new feature 
		IF feature_id is null THEN
			INSERT INTO `feature` (`f_feature_name`)
			VALUES(feature);
			SET feature_id=(SELECT LAST_INSERT_ID());
		END IF;
        
		-- check if such result exists if no then add
		SET testResult_id = (select `id` from `result` where `re_result_name`=testResult);
		-- add new result 
		IF testResult_id is null THEN
			INSERT INTO `result` (`re_result_name`)
			VALUES(testResult);
			SET testResult_id=(SELECT LAST_INSERT_ID());
		END IF;
        
        IF testAuthor is not null THEN
			-- check if such author exists if no then add
			SET testAuthor_id = (select `id` from `author` where `a_author_name`=testAuthor);
			-- add new author 
			IF testAuthor_id is null THEN
				INSERT INTO `author` (`a_author_name`)
				VALUES(testAuthor);
				SET testAuthor_id=(SELECT LAST_INSERT_ID());
			END IF;
		END IF;

        select reporter.run.id as runid,reporter.suite.id as suiteid 
        into run_id, suite_id 
        from reporter.run,reporter.suite where s_suite_uid=suiteUid AND reporter.suite.s_run_id=reporter.run.id;
		
		insert into test (t_additional_info, t_defect, feature_id, t_jira_id, result_id, test_author_id, t_test_run_duration, t_test_finish_date, t_test_name, t_test_start_date, t_test_uid, t_test_video, t_testrail_id, t_suite_id)
		values (additionalInfo, defect, feature_id, jiraId, testResult_id, testAuthor_id, testDuration, testFinishDate_date, testName, testStartDate_date, testUid, testVideoFileName, testrailId, suite_id);
        SET test_id=(SELECT LAST_INSERT_ID());
        
        UPDATE reporter.suite SET reporter.suite.s_suite_finish_date = testFinishDate_date WHERE reporter.suite.id = suite_id;
        
        UPDATE reporter.run SET reporter.run.r_run_finish_date = testFinishDate_date,reporter.run.r_is_run_finished=0  WHERE reporter.run.id = run_id;
        
        select run_id,suite_id,test_id;

    COMMIT WORK;

END$$

DROP PROCEDURE IF EXISTS `close_running`$$
CREATE DEFINER=`admin`@`%` PROCEDURE `close_running` ()  BEGIN
    declare maxduration,currentduration int;
    DECLARE curaction varchar(255);
    DECLARE done INT DEFAULT FALSE;
    DECLARE upperprcnt,upperlimit, lowerprcnt,lowerlimit, totalcount int;
    Declare RunId, RunDuration INT;
    Declare RunName VARCHAR(255);
    Declare StartDate,FinishDate DATETIME;
    DEClARE cursorruns CURSOR FOR
        select run.id as RunId, r_run_name AS RunName,r_run_start_date AS StartDate,r_run_finish_date AS FinishDate,r_run_duration AS RunDuration
        from `reporter`.`run`
        where r_is_run_finished=false;
    declare continue handler for not found set done = true;

-- set here upper and lower percentages to cut them off
    set upperprcnt=10;
    set lowerprcnt=10;
    OPEN cursorruns;

    run_loop : LOOP
        FETCH cursorruns INTO RunId, RunName,StartDate,FinishDate,RunDuration;
        IF done THEN
            LEAVE run_loop;
        END IF;
        set totalcount=(select count(r_run_duration) from `reporter`.`run` where  r_run_duration is not null and r_run_duration>20 and r_run_name=RunName);
        if totalcount>=10 then
			set lowerlimit=(select(ROUND(lowerprcnt*100/totalcount)));
            set upperlimit=totalcount-lowerlimit-(select(ROUND(upperprcnt*100/totalcount)));
            -- SET maxduration=(select max(r_run_duration) from `reporter`.`run` where r_run_name=RunName);
            SET maxduration=(select AVG(average) from (select r_run_duration as average from `reporter`.`run` where r_run_duration is not null and r_run_duration>20 and r_run_name=RunName limit lowerlimit,upperlimit) as limited);
        else
            SET maxduration=(select avg(r_run_duration) from `reporter`.`run` where r_run_name=RunName);
        end if;
        if maxduration is null then
            set maxduration=300;
        end if;
        set currentduration=timestampdiff(SECOND,StartDate,UTC_TIMESTAMP());
        if maxduration<currentduration then
            set curaction='close';
            update `reporter`.`run` set r_is_run_finished=true where run.id=RunId;
        else
            set curaction='runnning';
        end if;

        select CONCAT(RunId,', ',maxduration,', ',currentduration,', ',StartDate,', ', curaction,', ',UTC_TIMESTAMP(),',',upperlimit,',',lowerlimit);
    END LOOP;

    CLOSE cursorruns;

END$$

DROP PROCEDURE IF EXISTS `count_runs`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `count_runs` (IN `userenv` VARCHAR(255), IN `testtypeid` INT, IN `teamid` INT, IN `featureid` INT, IN `startdate` DATETIME, IN `enddate` DATETIME, IN `isdevrun` BOOL, IN `equalrunname` VARCHAR(255), IN `likerunname` VARCHAR(255), IN `equalversion` LONGTEXT, IN `likeversion` LONGTEXT)  BEGIN
    DECLARE querySelectPart text;
    DECLARE queryWherePart text;
    DECLARE queryLimitPart text;
    SET queryWherePart = "";
    SET queryLimitPart = "";

    SET querySelectPart =
            "select COUNT(1) as total from `reporter`.`run`,`reporter`.`environment`,`reporter`.`team` where ";

    SET queryWherePart = CONCAT(queryWherePart, "run.r_run_finish_date is not null ");

    IF userenv is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "e_env_name='", userenv, "'");
    END IF;

    IF testtypeid is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "run.test_type_id=", testtypeid);
    END IF;

    IF teamid is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "run.team_id=", teamid);
    END IF;

    IF startdate is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_start_date>='", startdate, "'");
    END IF;

    IF enddate is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_finish_date<='", enddate, "'");
    END IF;

    IF NOT isdevrun THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_is_developement_run=", "false");
    END IF;

    IF equalrunname IS NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_name='", equalrunname, "'");
    END IF;

    IF likerunname IS NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_name like '%", likerunname, "%'");
    END IF;

    IF (equalversion <> '' OR equalversion is NULL) AND likeversion is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        IF equalversion <> '' AND equalversion is not null AND likeversion is not NULL THEN
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version='", equalversion, "'");
        else
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version is null ");
        end if;
    END IF;

    IF (likeversion <> '' OR likeversion is NULL) AND equalversion is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        IF likeversion <> '' AND likeversion is not null AND equalversion is not NULL THEN
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version like '%", likeversion, "%'");
        else
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version is null ");
        end if;
    END IF;

    if queryWherePart <> '' then
        SET queryWherePart = CONCAT(queryWherePart, " AND ");
    End if;


    SET @query = CONCAT(querySelectPart, queryWherePart, "(run.environment_id=environment.id AND run.team_id=team.id) ",
                        "order by run.r_run_start_date desc");

    --  SELECT @query;
    PREPARE statment1 FROM @query;
    EXECUTE statment1;
    DEALLOCATE PREPARE statment1;

END$$

DROP PROCEDURE IF EXISTS `delete_old_logs`$$
CREATE DEFINER=`admin`@`%` PROCEDURE `delete_old_logs` (IN `number_of_days` INT)  delete_old_logs:BEGIN
    SET FOREIGN_KEY_CHECKS=0;
    IF number_of_days is NULL THEN
        LEAVE delete_old_logs;
    END IF;
-- delete old logs
    delete `reporter`.`log` from `reporter`.`log` where `reporter`.`log`.`l_log_added_timestamp`<DATE_SUB(current_timestamp,INTERVAL number_of_days DAY);

-- delete old ids from mapping table for logs that aren't existing anymore
--    delete FROM reporter.test_log where log_id not in (SELECT id FROM log);
--    delete FROM test_log where log_id<(SELECT min(id) FROM reporter.log);
    SET FOREIGN_KEY_CHECKS=1;
END$$

DROP PROCEDURE IF EXISTS `delete_old_runs`$$
CREATE DEFINER=`admin`@`%` PROCEDURE `delete_old_runs` (IN `number_of_days` INT, IN `is_dev_run` BOOL)  delete_old_runs:BEGIN
    SET FOREIGN_KEY_CHECKS=0;
    IF number_of_days is NULL THEN
        LEAVE delete_old_runs;
    END IF;
    delete `reporter`.`run` from `reporter`.`run`,
                                 `reporter`.`suite`,
                                 `reporter`.`test`,
                                 `reporter`.`log`
    where  `run`.`r_run_start_date`<DATE_SUB(current_timestamp,INTERVAL number_of_days DAY) AND `run`.`r_is_developement_run`=is_dev_run AND (reporter.suite.s_run_id=reporter.run.`id` AND
        `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id`  AND
        `reporter`.`log`.`l_test_id` = `reporter`.`test`.`id`);
    SET FOREIGN_KEY_CHECKS=1;
END$$

DROP PROCEDURE IF EXISTS `get_blamed`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_blamed` (IN `days` INT, IN `statuses` TEXT, IN `who` TEXT, IN `teamid` INT, IN `runid` INT)  BEGIN
    declare statusesOri text;
    declare whoOri text;
    declare querySelectPart text;
    declare queryWherePart text;
    declare queryStatusWherePart text;
    declare queryWhoWherePart text;
    declare SubStrLen int default 0;
    declare strLen int default 0;
    set queryWherePart="";
    set queryStatusWherePart="";
    set queryWhoWherePart="";
    set statusesOri=statuses;
    set whoOri=who;


    set querySelectPart="select `run`.`id` as RUNID,`test`.`id` as TESTID,`author`.`a_author_name` AS Author,s_suite_name as SuiteName,t_test_name as TestName,t_testrail_id as TestRailID,r_run_start_date as RunStartDate,r_run_finish_date as RunFinishDate,TIME_FORMAT(SEC_TO_TIME(t_test_run_duration / 1000), \"%H:%i:%s\") as TestDuration,re_result_name as TestResult,t_defect as Defect
from `reporter`.`run`,`reporter`.`suite`,`reporter`.`test`,`reporter`.`result`,`reporter`.`author`
where ";

    IF runid is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, " `run`.`id`=", runid, " ");
    END IF;


    IF statuses IS NULL THEN
        SET statuses = '';
    END IF;
    status_parts:
    LOOP
        SET strLen = LENGTH(statuses);
        if queryStatusWherePart = '' then
            SET queryStatusWherePart = CONCAT(queryStatusWherePart, " ( ");
        End if;
        SET queryStatusWherePart = CONCAT(queryStatusWherePart,"re_result_name='",SUBSTRING_INDEX(statuses, ',', 1), "' OR ");
        SET SubStrLen = LENGTH(SUBSTRING_INDEX(statuses, ',', 1))+2;
        SET statuses = MID(statuses, SubStrLen, strLen);
        IF statuses = '' THEN
            SET queryStatusWherePart =LEFT(queryStatusWherePart,LENGTH(queryStatusWherePart)-3);
            SET queryStatusWherePart = CONCAT(queryStatusWherePart, " ) ");
            LEAVE status_parts;
        END IF;
    END LOOP status_parts;

    if (statusesOri is not null And statusesOri <> '' )  then
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart,queryStatusWherePart);
    End if;


    IF who IS NULL THEN
        SET who = '';
    END IF;

    who_parts:
    LOOP
        SET strLen = LENGTH(who);
        if queryWhoWherePart = '' then
            SET queryWhoWherePart = CONCAT(queryWhoWherePart, " ( ");
        End if;
        SET queryWhoWherePart = CONCAT(queryWhoWherePart,"`author`.`a_author_name`='",SUBSTRING_INDEX(who, ',', 1), "' OR ");
        SET SubStrLen = LENGTH(SUBSTRING_INDEX(who, ',', 1))+2;
        SET who = MID(who, SubStrLen, strLen);
        IF who = '' THEN
            SET queryWhoWherePart =LEFT(queryWhoWherePart,LENGTH(queryWhoWherePart)-3);
            SET queryWhoWherePart = CONCAT(queryWhoWherePart, " ) ");
            LEAVE who_parts;
        END IF;
    END LOOP who_parts;

    if (whoOri is not null AND whoOri<>'') then
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart,queryWhoWherePart);
    End if;


    IF days is NOT NULL AND runid is NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, " t_test_finish_date>DATE_SUB(current_timestamp,INTERVAL ", days, " DAY) ");
    END IF;

    if queryWherePart <> '' then
        SET queryWherePart = CONCAT(queryWherePart, " AND ");
    End if;
-- r_is_developement_run=false AND
    SET @query = CONCAT(querySelectPart, queryWherePart, "  (reporter.suite.s_run_id=reporter.run.`id`
    AND `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id`  AND
         `reporter`.`test`.`result_id` = `reporter`.`result`.`id` AND `reporter`.`test`.`test_author_id`=`reporter`.`author`.`id`) ",
                        "order by `author`.`a_author_name`,s_suite_name,t_test_name,`test`.`id` DESC");

    /* select `author`.`a_author_name` AS Author,
           `suite`.`id`                                                     as TestTable_SuiteID,
            `test`.`id`                                                      as TestTable_TestID,
            t_test_name                                                      as TestTable_TestName,
            `test`.`t_test_uid`                                              as TestTable_TestUUID,
            t_testrail_id                                                    as TestTable_TestRailID,
            r_run_start_date                                     as RunStartDate,
            r_run_finish_date                                    as RunFinishDate,
            TIME_FORMAT(SEC_TO_TIME(t_test_run_duration / 1000), "%H:%i:%s") as TestTable_TestDuration,
            re_result_name                                                   as TestTable_TestResult,
            t_defect as TestTable_Defect

     from `reporter`.`run`,
          `reporter`.`suite`,
          `reporter`.`test`,
          `reporter`.`run_suite`,
          `reporter`.`suite_test`,
          `reporter`.`result`,
          `reporter`.`author`
     where t_test_finish_date>DATE_SUB(current_timestamp,INTERVAL days DAY)
     AND re_result_name<>'PASS'
       AND (`reporter`.`run`.`id` = `reporter`.`run_suite`.`run_id`
       AND `reporter`.`run_suite`.`suite_id` = `reporter`.`suite`.`id` AND
            `reporter`.`suite_test`.`suite_id` = `reporter`.`suite`.`id`
       AND `reporter`.`suite_test`.`test_id` = `reporter`.`test`.`id` AND
            `reporter`.`test`.`result_id` = `reporter`.`result`.`id` AND `reporter`.`test`.`test_author_id`=`reporter`.`author`.`id`)
     order by `author`.`a_author_name`,`test`.`id` DESC;
     */
-- SELECT @query;
    PREPARE statment1 FROM @query;
    EXECUTE statment1;
    DEALLOCATE PREPARE statment1;

END$$

DROP PROCEDURE IF EXISTS `get_feature`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_feature` (IN `RUNID` INT, IN `UUID` VARCHAR(255))  get_suit :
BEGIN
    DECLARE runuuidvar VARCHAR(255);
    DECLARE runidvar INT;
    SET runuuidvar = UUID;
    SET runidvar = RUNID;

    IF runidvar is null THEN
        SET runidvar = (SELECT `run`.`id`
                        from `reporter`.`run`
                        where r_run_uid = runuuidvar);
    END IF;

    IF runidvar = 0 OR runidvar is NULL THEN
        LEAVE get_suit;
    END IF;

    select runuuidvar;
    select runidvar;

    call get_run_details(runidvar);


    SELECT A.SuiteTable_FeatureID as SuiteTable_FeatureID,
           SuiteTable_FeatureName,
           (CASE
                when SuiteTable_FAIL > 0 Then 'FAIL'
                when `SuiteTable_ERROR` > 0 then 'ERROR'
                when SuiteTable_TOTAL = SuiteTable_SKIP then 'SKIP'
                when SuiteTable_TOTAL = SuiteTable_PASS then 'PASS'
                when SuiteTable_TOTAL = (SuiteTable_PASS + SuiteTable_SKIP) then 'PASS'
               END) as SuiteTable_FeatureStatus,
           SuiteTable_FAIL,
           `SuiteTable_ERROR`,
           SuiteTable_SKIP,
           SuiteTable_PASS,
           SuiteTable_TOTAL
    FROM (
             SELECT COUNT(*)                                                   AS SuiteTable_TOTAL,
                    SUM(`re_result_name` = 'PASS')                             AS SuiteTable_PASS,
                    SUM(`re_result_name` = 'SKIP')                             AS SuiteTable_SKIP,
                    SUM(`re_result_name` = 'FAIL')                             AS SuiteTable_FAIL,
                    SUM(`re_result_name` = 'ERROR')                            AS `SuiteTable_ERROR`,
                    `feature`.`id` as SuiteTable_FeatureID,
                    f_feature_name                                                   as SuiteTable_FeatureName

             from `reporter`.`run`,
                  `reporter`.`suite`,
                  `reporter`.`test`,
                  `reporter`.`result`,
                  `reporter`.`feature`
             where `reporter`.`run`.`id` = runidvar
               AND (reporter.suite.s_run_id=reporter.run.`id` AND
                    `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id` AND
                    `reporter`.`test`.`result_id` = `reporter`.`result`.`id` AND
                    `reporter`.`test`.`feature_id`=`reporter`.`feature`.`id`)
             group by SuiteTable_FeatureID
             order by SuiteTable_FeatureName) as A;

    -- t_test_start_date as TestTable_TestStartDate,t_test_finish_date as TestTable_TestFinishDate,
    SELECT          `feature`.`id` as TestTable_FeatureID,
                    `test`.`id`                                                      as TestTable_TestID,
                    t_test_video	as TestTable_TestVideo,
                    t_test_name                                                      as TestTable_TestName,
                    `test`.`t_test_uid`                                              as TestTable_TestUUID,
                    t_testrail_id                                                    as TestTable_TestRailID,
                    t_jira_id                                                    as TestTable_JiraID,
                    TIME_FORMAT(SEC_TO_TIME(t_test_run_duration / 1000), "%H:%i:%s") as TestTable_TestDuration,
                    re_result_name                                                   as TestTable_TestResult,
                    `author`.`a_author_name` AS TestTable_Author,
                    t_defect as TestTable_Defect
    from `reporter`.`run`,
         `reporter`.`suite`,
         `reporter`.`test`,
         `reporter`.`result`,
         `reporter`.`author`,
         `reporter`.`feature`
    where `reporter`.`run`.`id` = runidvar
      AND (reporter.suite.s_run_id=reporter.run.`id` AND
           `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id`  AND
           `reporter`.`test`.`result_id` = `reporter`.`result`.`id` AND
           `reporter`.`test`.`feature_id`=`reporter`.`feature`.`id` and
           `reporter`.`test`.`test_author_id`=`reporter`.`author`.`id`)
    order by f_feature_name, `test`.`id`;


END$$

DROP PROCEDURE IF EXISTS `get_runs`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_runs` (IN `userenv` VARCHAR(255), IN `limitstartrow` INT, IN `limitnumberofrows` INT, IN `testtypeid` INT, IN `teamid` INT, IN `featureid` INT, IN `startdate` DATETIME, IN `enddate` DATETIME, IN `isdevrun` BOOL, IN `equalrunname` VARCHAR(255), IN `likerunname` VARCHAR(255), IN `equalversion` LONGTEXT, IN `likeversion` LONGTEXT)  BEGIN
    DECLARE querySelectPart text;
    DECLARE queryWherePart text;
    DECLARE queryLimitPart text;
    SET queryWherePart = "";
    SET queryLimitPart = "";

    /* select run.id as RUNID,run.r_run_name as 'Name',run.r_run_start_date as 'Start at',run.r_run_finish_date as 'Finished at',run.r_run_duration as 'Duration',environment.e_env_name as 'Environment',team.tm_team_name as 'Team',
      IFNULL((SELECT CONCAT('<td>',CAST(SUM(`re_result_name` = 'FAIL') as char),
    '</td><td>',CAST(SUM(`re_result_name` = 'ERROR') as char),
    '</td><td>',CAST(SUM(`re_result_name` = 'SKIP') as char),
    '</td><td>',CAST(SUM(`re_result_name` = 'PASS') as char),
    '</td><td>',CAST(COUNT(*) as char),'</td>')
      from `reporter`.`run`,`reporter`.`suite`,`reporter`.`test`,`reporter`.`run_suite`,`reporter`.`suite_test`,`reporter`.`result`
      where `reporter`.`run`.`id`=RUNID AND (`reporter`.`run`.`id`=`reporter`.`run_suite`.`run_id`
            AND `reporter`.`run_suite`.`suite_id`=`reporter`.`suite`.`id` AND `reporter`.`suite_test`.`suite_id`=`reporter`.`suite`.`id`
                    AND `reporter`.`suite_test`.`test_id`=`reporter`.`test`.`id` AND `reporter`.`test`.`result_id`=`reporter`.`result`.`id`)),'<td></td><td></td><td></td><td></td><td></td>') as '<th>FAIL</th><th>ERROR</th><th>SKIP</th><th>PASS</th><th>Total</th>'
      from `reporter`.`run`,`reporter`.`environment`,`reporter`.`team` where run.r_run_finish_date is not null  AND e_env_name='dev' AND (run.environment_id=environment.id AND run.team_id=team.id) order by run.r_run_start_date desc limit 0,5
    */
    -- ,out total int
    -- select COUNT(*) into total from `reporter`.`run`,`reporter`.`environment`,`reporter`.`team` where run.environment_id=environment.id AND run.team_id=team.id;

    SET querySelectPart = "select run.id as RUNID,run.r_run_name as 'Name',if(r_is_run_finished,'Finished','InProgress') as 'Status',ifnull(r_build_version,'N/A') as Version,if(r_is_developement_run,'Yes','No') as DevRun,run.r_run_start_date as 'Start at',run.r_run_finish_date as 'Finished at',TIME_FORMAT(SEC_TO_TIME(run.r_run_duration),\"%H:%i:%s\") as 'Duration',environment.e_env_name as 'Environment',team.tm_team_name as 'Team',
	IFNULL((SELECT CONCAT('<td>',CAST(SUM(`re_result_name` = 'FAIL') as char),
'</td><td>',CAST(SUM(`re_result_name` = 'ERROR') as char),
'</td><td>',CAST(SUM(`re_result_name` = 'SKIP') as char),
'</td><td>',CAST(SUM(`re_result_name` = 'PASS') as char),
'</td><td>',CAST(COUNT(*) as char),'</td>')
	from `reporter`.`run` FORCE INDEX(PRIMARY),`reporter`.`suite`,`reporter`.`test`,`reporter`.`result` FORCE INDEX(PRIMARY)
	where `reporter`.`run`.`id`=RUNID AND (reporter.suite.s_run_id=reporter.run.`id` AND `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id` 
	AND `reporter`.`test`.`result_id`=`reporter`.`result`.`id`)),'<td></td><td></td><td></td><td></td><td></td>') as '<th>FAIL</th><th>ERROR</th><th>SKIP</th><th>PASS</th><th>Total</th>'
	from `reporter`.`run` FORCE INDEX(PRIMARY),`reporter`.`environment`,`reporter`.`team` where ";

    /* don't forget to update count_runs procedure with the same conditions */

    SET queryWherePart = CONCAT(queryWherePart, "run.r_run_finish_date is not null ");

    IF userenv is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "e_env_name='", userenv, "'");
    END IF;

    IF testtypeid is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "run.test_type_id=", testtypeid);
    END IF;

    IF teamid is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "run.team_id=", teamid);
    END IF;

    IF startdate is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_start_date>='", startdate, "'");
    END IF;

    IF enddate is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_finish_date<='", enddate, "'");
    END IF;

    IF NOT isdevrun THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_is_developement_run=", "false");
    END IF;



    IF equalrunname IS NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_name='", equalrunname, "'");
    END IF;

    IF likerunname IS NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_name like '%", likerunname, "%'");
    END IF;

    IF (equalversion <> '' OR equalversion is NULL) AND likeversion is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        IF equalversion <> '' AND equalversion is not null AND likeversion is not NULL THEN
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version='", equalversion, "'");
        else
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version is null ");
        end if;
    END IF;

    IF (likeversion <> '' OR likeversion is NULL) AND equalversion is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        IF likeversion <> '' AND likeversion is not null AND equalversion is not NULL THEN
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version like '%", likeversion, "%'");
        else
            SET queryWherePart = CONCAT(queryWherePart, "r_build_version is null ");
        end if;
    END IF;

    if queryWherePart <> '' then
        SET queryWherePart = CONCAT(queryWherePart, " AND ");
    End if;

    -- limit 2,5
    IF (limitstartrow is not NULL) AND (limitnumberofrows is not NULL) then
        SET queryLimitPart = CONCAT(" limit ", limitstartrow, ",", limitnumberofrows);
    end if;

    SET @query = CONCAT(querySelectPart, queryWherePart, "(run.environment_id=environment.id AND run.team_id=team.id) ",
                        "order by run.r_run_start_date desc", queryLimitPart);

    -- SELECT @query;
    PREPARE statment1 FROM @query;
    EXECUTE statment1;
    DEALLOCATE PREPARE statment1;

END$$

DROP PROCEDURE IF EXISTS `get_run_details`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_run_details` (IN `RUNID` INT)  BEGIN


    Select RunName,
           EnvName,
           TeamName,
           `Status`,
           (CASE
                when FAIL > 0 Then 'FAIL'
                when `ERROR` > 0 then 'ERROR'
                when TOTAL = SKIP then 'SKIP'
                when TOTAL = PASS then 'PASS'
                when TOTAL = (PASS + SKIP) then 'PASS'
               END) as RunResult,
           version,
           DevRun,
           RunStartDate,
           RunFinishDate,
           RunDuration,
           FAIL,
           `ERROR`,
           SKIP,
           PASS,
           TOTAL,
           round(( FAIL*100/TOTAL),2) as `FAIL%`,
           round(( `ERROR`*100/TOTAL),2) as `ERROR%`,
           round(( SKIP*100/TOTAL),2) as `SKIP%`,
           round(( PASS*100/TOTAL),2) as `PASS%`

    from (select COUNT(*)                                             AS TOTAL,
                 SUM(`re_result_name` = 'PASS')                       AS PASS,
                 SUM(`re_result_name` = 'SKIP')                       AS SKIP,
                 SUM(`re_result_name` = 'FAIL')                       AS FAIL,
                 SUM(`re_result_name` = 'ERROR')                      AS `ERROR`,
                 r_run_name                                           as RunName,
                 if(r_is_run_finished,'Finished','InProgress') as `Status`,
                 r_run_uid                                            as RunUUID,
                 r_run_start_date                                     as RunStartDate,
                 r_run_finish_date                                    as RunFinishDate,
                 TIME_FORMAT(SEC_TO_TIME(r_run_duration), "%H:%i:%s") as RunDuration,
                 ifnull(r_build_version, 'N/A')                       as version,
                 e_env_name                                           as EnvName,
                 tm_team_name                                         as TeamName,
                 if(r_is_developement_run,'Yes','No') as DevRun
          from `reporter`.`run`,
               `reporter`.`environment`,
               `reporter`.`team`,
               `reporter`.`suite`,
               `reporter`.`test`,
               `reporter`.`result`
          where run.id = RUNID
            AND run.environment_id = environment.id
            AND run.team_id = team.id
            AND (reporter.suite.s_run_id=reporter.run.`id` AND
                 `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id` AND
                 `reporter`.`test`.`result_id` = `reporter`.`result`.`id`)) as RunResult;

END$$

DROP PROCEDURE IF EXISTS `get_suit`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_suit` (IN `RUNID` INT, IN `UUID` VARCHAR(255))  get_suit :
BEGIN
    DECLARE runuuidvar VARCHAR(255);
    DECLARE runidvar INT;
    SET runuuidvar = UUID;
    SET runidvar = RUNID;

    IF runidvar is null THEN
        SET runidvar = (SELECT `run`.`id`
                        from `reporter`.`run`
                        where r_run_uid = runuuidvar);
    END IF;

    IF runidvar = 0 OR runidvar is NULL THEN
        LEAVE get_suit;
    END IF;

    select runuuidvar;
    select runidvar;

    /*
    Select  RunName,
      (CASE
      when FAIL>0 Then 'FAIL'
        when `ERROR`>0 then 'ERROR'
        when TOTAL=SKIP then 'SKIP'
        when TOTAL=PASS then 'PASS'
      END) as RunStatus,
        RunStartDate, RunFinishDate,RunDuration,EnvName,TeamName,FAIL,`ERROR`,SKIP,PASS,
        round(( FAIL*100/TOTAL),2) as `FAIL%`,
        round(( `ERROR`*100/TOTAL),2) as `ERROR%`,
        round(( SKIP*100/TOTAL),2) as `SKIP%`,
        round(( PASS*100/TOTAL),2) as `PASS%`,
        TOTAL
      from (select COUNT(*) AS TOTAL,
        SUM(`re_result_name` = 'PASS')AS PASS,
        SUM(`re_result_name` = 'SKIP')AS SKIP,
        SUM(`re_result_name` = 'FAIL') AS FAIL,
        SUM(`re_result_name` = 'ERROR')AS `ERROR`, r_run_name as RunName,r_run_uid as RunUUID,r_run_start_date as RunStartDate,r_run_finish_date as RunFinishDate,r_run_duration as RunDuration,e_env_name as EnvName,tm_team_name as TeamName
    from `reporter`.`run`,`reporter`.`environment`,`reporter`.`team`,`reporter`.`suite`,`reporter`.`test`,`reporter`.`run_suite`,`reporter`.`suite_test`,`reporter`.`result`
    where run.id=runidvar AND run.environment_id=environment.id AND run.team_id=team.id AND (`reporter`.`run`.`id`=`reporter`.`run_suite`.`run_id`
            AND `reporter`.`run_suite`.`suite_id`=`reporter`.`suite`.`id` AND `reporter`.`suite_test`.`suite_id`=`reporter`.`suite`.`id`
                    AND `reporter`.`suite_test`.`test_id`=`reporter`.`test`.`id` AND `reporter`.`test`.`result_id`=`reporter`.`result`.`id`) ) as RunResult;
                    */

    call get_run_details(runidvar);


    /* SELECT COUNT(*) AS TOTAL,
        SUM(`re_result_name` = 'PASS')AS PASS,
        SUM(`re_result_name` = 'SKIP')AS SKIP,
        SUM(`re_result_name` = 'FAIL') AS FAIL,
        SUM(`re_result_name` = 'ERROR')AS `ERROR`,`suite`.`id` as SuiteID, s_suite_name as SuiteName,s_suite_start_date as SuiteStartDate,s_suite_finish_date as SuiteFinishDate,s_suite_run_duration as SuiteDuration
    from `reporter`.`run`,`reporter`.`suite`,`reporter`.`run_suite`
    where `reporter`.`run`.`id`=runidvar AND (`reporter`.`run`.`id`=`reporter`.`run_suite`.`run_id`
    AND `reporter`.`run_suite`.`suite_id`=`reporter`.`suite`.`id`)  group by SuiteID;
    */
    /*
    SELECT A.SuiteTable_SuiteID,SuiteTable_SuiteName,
     (CASE
      when SuiteTable_FAIL>0 Then 'FAIL'
        when `SuiteTable_ERROR`>0 then 'ERROR'
        when SuiteTable_TOTAL=SuiteTable_SKIP then 'SKIP'
        when SuiteTable_TOTAL=SuiteTable_PASS then 'PASS'
      END) as SuiteTable_SuiteStatus,
     SuiteTable_SuiteStartDate, SuiteTable_SuiteFinishDate, SuiteTable_SuiteDuration,SuiteTable_TOTAL,SuiteTable_PASS,SuiteTable_FAIL,`SuiteTable_ERROR`,SuiteTable_SKIP, TestID, TestName,TestRailID,TestStartDate,TestFinishDate,TestDuration,TestResult
    FROM (
    SELECT COUNT(*) AS SuiteTable_TOTAL,
        SUM(`re_result_name` = 'PASS')AS SuiteTable_PASS,
        SUM(`re_result_name` = 'SKIP')AS SuiteTable_SKIP,
        SUM(`re_result_name` = 'FAIL') AS SuiteTable_FAIL,
        SUM(`re_result_name` = 'ERROR')AS `SuiteTable_ERROR`,
        `suite`.`id` as SuiteTable_SuiteID, s_suite_name as SuiteTable_SuiteName,s_suite_start_date as SuiteTable_SuiteStartDate,s_suite_finish_date as SuiteTable_SuiteFinishDate,s_suite_run_duration as SuiteTable_SuiteDuration
      from `reporter`.`run`,`reporter`.`suite`,`reporter`.`test`,`reporter`.`run_suite`,`reporter`.`suite_test`,`reporter`.`result`
      where `reporter`.`run`.`id`=runidvar AND (`reporter`.`run`.`id`=`reporter`.`run_suite`.`run_id`
            AND `reporter`.`run_suite`.`suite_id`=`reporter`.`suite`.`id` AND `reporter`.`suite_test`.`suite_id`=`reporter`.`suite`.`id`
                    AND `reporter`.`suite_test`.`test_id`=`reporter`.`test`.`id` AND `reporter`.`test`.`result_id`=`reporter`.`result`.`id`)
                    group by SuiteTable_SuiteID order by SuiteTable_SuiteID) as A
    JOIN (
    SELECT `suite`.`id` as SuiteTable_SuiteID, `test`.`id` as TestID, t_test_name as TestName,t_testrail_id as TestRailID,t_test_start_date as TestStartDate,t_test_finish_date as TestFinishDate,t_test_run_duration as TestDuration, re_result_name as TestResult
      from `reporter`.`run`,`reporter`.`suite`,`reporter`.`test`,`reporter`.`run_suite`,`reporter`.`suite_test`,`reporter`.`result`
      where `reporter`.`run`.`id`=runidvar AND (`reporter`.`run`.`id`=`reporter`.`run_suite`.`run_id`
            AND `reporter`.`run_suite`.`suite_id`=`reporter`.`suite`.`id` AND `reporter`.`suite_test`.`suite_id`=`reporter`.`suite`.`id`
                    AND `reporter`.`suite_test`.`test_id`=`reporter`.`test`.`id` AND `reporter`.`test`.`result_id`=`reporter`.`result`.`id`)
                    order by `suite`.`id`,`test`.`id`) as B
    ON A.SuiteTable_SuiteID=B.SuiteTable_SuiteID;*/


    SELECT A.SuiteTable_SuiteID,
           SuiteTable_SuiteName,
           (CASE
                when SuiteTable_FAIL > 0 Then 'FAIL'
                when `SuiteTable_ERROR` > 0 then 'ERROR'
                when SuiteTable_TOTAL = SuiteTable_SKIP then 'SKIP'
                when SuiteTable_TOTAL = SuiteTable_PASS then 'PASS'
                when SuiteTable_TOTAL = (SuiteTable_PASS + SuiteTable_SKIP) then 'PASS'
               END) as SuiteTable_SuiteStatus,
           SuiteTable_SuiteStartDate,
           SuiteTable_SuiteFinishDate,
           SuiteTable_SuiteDuration,
           SuiteTable_FAIL,
           `SuiteTable_ERROR`,
           SuiteTable_SKIP,
           SuiteTable_PASS,
           SuiteTable_TOTAL
    FROM (
             SELECT COUNT(*)                                                   AS SuiteTable_TOTAL,
                    SUM(`re_result_name` = 'PASS')                             AS SuiteTable_PASS,
                    SUM(`re_result_name` = 'SKIP')                             AS SuiteTable_SKIP,
                    SUM(`re_result_name` = 'FAIL')                             AS SuiteTable_FAIL,
                    SUM(`re_result_name` = 'ERROR')                            AS `SuiteTable_ERROR`,
                    `suite`.`id`                                               as SuiteTable_SuiteID,
                    s_suite_name                                               as SuiteTable_SuiteName,
                    s_suite_start_date                                         as SuiteTable_SuiteStartDate,
                    s_suite_finish_date                                        as SuiteTable_SuiteFinishDate,
                    TIME_FORMAT(SEC_TO_TIME(s_suite_run_duration), "%H:%i:%s") as SuiteTable_SuiteDuration
             from `reporter`.`run`,
                  `reporter`.`suite`,
                  `reporter`.`test`,
                  `reporter`.`result`
             where `reporter`.`run`.`id` = runidvar
               AND (reporter.suite.s_run_id=reporter.run.`id` AND
                    `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id`  AND
                    `reporter`.`test`.`result_id` = `reporter`.`result`.`id`)
             group by SuiteTable_SuiteID
             order by SuiteTable_SuiteID) as A;

    -- t_test_start_date as TestTable_TestStartDate,t_test_finish_date as TestTable_TestFinishDate,
    SELECT `suite`.`id`                                                     as TestTable_SuiteID,
           `test`.`id`                                                      as TestTable_TestID,
           t_test_video	as TestTable_TestVideo,
           t_test_name                                                      as TestTable_TestName,
           `test`.`t_test_uid`                                              as TestTable_TestUUID,
           t_testrail_id                                                    as TestTable_TestRailID,
           t_jira_id                                                    as TestTable_JiraID,
           TIME_FORMAT(SEC_TO_TIME(t_test_run_duration / 1000), "%H:%i:%s") as TestTable_TestDuration,
           re_result_name                                                   as TestTable_TestResult,
           `author`.`a_author_name` AS TestTable_Author,
           t_defect as TestTable_Defect
    from `reporter`.`run`,
         `reporter`.`suite`,
         `reporter`.`test`,
         `reporter`.`result`,
         `reporter`.`author`
    where `reporter`.`run`.`id` = runidvar
      AND (reporter.suite.s_run_id=reporter.run.`id` AND
           `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id`  AND
           `reporter`.`test`.`result_id` = `reporter`.`result`.`id` AND `reporter`.`test`.`test_author_id`=`reporter`.`author`.`id`)
    order by `suite`.`id`, `test`.`id`;


    /* begin
    DECLARE done INT DEFAULT FALSE;
    Declare SuiteIDvar, SuiteDurationvar INT;
    Declare SuiteNamevar VARCHAR(255);
    Declare SuiteStartDatevar,SuiteFinishDatevar DATETIME;
    DEClARE cursorsuits CURSOR FOR
    select r_run_name AS RunName,r_run_start_date AS StartDate,r_run_finish_date AS FinishDate,r_run_duration AS RunDuration,e_env_name AS EnvName,tm_team_name AS TeamName
    from `reporter`.`run`,`reporter`.`environment`,`reporter`.`team`
    where run.id=runidvar AND run.environment_id=environment.id AND run.team_id=team.id;
    declare continue handler for not found set done = true;

    OPEN cursorsuits;

    suits_loop : LOOP
        FETCH cursorsuits INTO SuiteIDvar, SuiteNamevar,SuiteStartDatevar,SuiteFinishDatevar,SuiteDurationvar;
        IF done THEN
          LEAVE suits_loop;
        END IF;
        SELECT * from `reporter`.`suite`,`reporter`.`test`,`reporter`.`run_suite`,`reporter`.`suite_test`,`reporter`.`result` where
            suite.id=SuiteIDvar
            AND (`reporter`.`suite_test`.`suite_id`=`reporter`.`suite`.`id`
                    AND `reporter`.`suite_test`.`test_id`=`reporter`.`test`.`id` AND `reporter`.`test`.`result_id`=`reporter`.`result`.`id`);
        select SuiteIDvar;
        select SuiteNamevar;
        select SuiteStartDatevar;
        select SuiteFinishDatevar;
        select SuiteDurationvar;
    END LOOP;

    CLOSE cursorsuits;
    end;
    */


    -- SELECT Id, dateCreated
    -- INTO iId, dCreate
    -- FROM products
    -- WHERE pName = iName

END$$

DROP PROCEDURE IF EXISTS `get_test_details`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_test_details` (IN `TESTID` INT)  get_test_details :
BEGIN
    IF TESTID = 0 OR TESTID is NULL THEN
        LEAVE get_test_details;
    END IF;
    select count(l_screenshot_file_name) as metaamount
    from `reporter`.`log`,
         `reporter`.`test`
    where test.id = TESTID
      and l_screenshot_file_name is not null
      and (`reporter`.`log`.`l_test_id` = `reporter`.`test`.`id`);
    select log.id as logid, IF(log.l_screenshot_file_name IS NULL,NULL,"--") as meta, log.l_log as logline
    from `reporter`.`log`,
         `reporter`.`test`
    where test.id = TESTID
      and (`reporter`.`log`.`l_test_id` = `reporter`.`test`.`id`);
END$$

DROP PROCEDURE IF EXISTS `get_test_history`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_test_history` (IN `RUNNAME` VARCHAR(255), IN `TESTNAME` VARCHAR(255), IN `startdate` DATETIME, IN `enddate` DATETIME)  get_test_history :
BEGIN
    DECLARE querySelectPart text;
    DECLARE queryWherePart text;
    DECLARE queryLimitPart text;
    SET queryWherePart = "";

    IF RUNNAME is NULL OR TESTNAME is NULL THEN
        LEAVE get_test_history;
    END IF;
    SET querySelectPart = "SELECT `run`.`id` as RunID,`test`.`id` as TestID,r_run_name as RunName,e_env_name as EnvName,
		 `author`.`a_author_name` AS Author,
         ifnull(r_build_version,'N/A') as Version,
         t_test_start_date as TestStartDate,t_test_finish_date as TestFinishDate,
         TIME_FORMAT(SEC_TO_TIME(t_test_run_duration / 1000), \"%H:%i:%s\") as TestDuration,
         re_result_name as TestResult
  FROM reporter.run,reporter.test,reporter.result,reporter.suite,reporter.environment,`reporter`.`author`
  where ";
    IF startdate is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_start_date>='", startdate, "'");
    END IF;

    IF enddate is NOT NULL THEN
        if queryWherePart <> '' then
            SET queryWherePart = CONCAT(queryWherePart, " AND ");
        End if;
        SET queryWherePart = CONCAT(queryWherePart, "r_run_finish_date<='", enddate, "'");
    END IF;

    if queryWherePart <> '' then
        SET queryWherePart = CONCAT(queryWherePart, " AND ");
    End if;

    SET queryWherePart = CONCAT(queryWherePart, " r_run_name='", RUNNAME, "'"," AND t_test_name='",TESTNAME,"' ");

    SET @query = CONCAT(querySelectPart, queryWherePart, " AND (reporter.suite.s_run_id=reporter.run.`id`
    AND `reporter`.`suite`.`id`= `reporter`.`test`.`t_suite_id`  AND
         `reporter`.`test`.`result_id`=`reporter`.`result`.`id` AND
         `reporter`.`run`.`environment_id`=`reporter`.`environment`.id AND `reporter`.`test`.`test_author_id`=`reporter`.`author`.`id`)
  order by `reporter`.`run`.`id` desc");

    -- SELECT @query;
    PREPARE statment1 FROM @query;
    EXECUTE statment1;
    DEALLOCATE PREPARE statment1;
END$$

DROP PROCEDURE IF EXISTS `mesure_time_execution`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `mesure_time_execution` ()  BEGIN
    declare count,RunningTimeSec,RunningTime,t1,t2 bigint;
    SET t1 = FROM_UNIXTIME(UNIX_TIMESTAMP(CONCAT(DATE(NOW()), ' ', CURTIME(3))));
    SET count=0;
    WHILE count < 500 DO
            call close_running();
            SET count = count + 1;
        END WHILE;
    SET t2 = FROM_UNIXTIME(UNIX_TIMESTAMP(CONCAT(DATE(NOW()), ' ', CURTIME(3))));
    -- SET RunningTimeSec = t2 - t1;
-- SET RunningTime = RunningTimeSec;
    select timestampdiff(microsecond,t1,t2)/1000;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `author`
--

DROP TABLE IF EXISTS `author`;
CREATE TABLE IF NOT EXISTS `author` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `a_author_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `a_author_name` (`a_author_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `environment`
--

DROP TABLE IF EXISTS `environment`;
CREATE TABLE IF NOT EXISTS `environment` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `e_env_name` varchar(63) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `e_env_name` (`e_env_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `feature`
--

DROP TABLE IF EXISTS `feature`;
CREATE TABLE IF NOT EXISTS `feature` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `f_feature_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `f_feature_name` (`f_feature_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
CREATE TABLE IF NOT EXISTS `log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `l_log` longtext DEFAULT NULL,
  `l_screenshot_file_name` longblob DEFAULT NULL,
  `l_log_added_timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  `l_screenshot_preview` blob DEFAULT NULL,
  `l_screenshot_type` varchar(127) DEFAULT NULL,
  `l_test_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `l_log_added_timestamp_idx` (`l_log_added_timestamp`),
  KEY `fk_log_tid_idx` (`l_test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 KEY_BLOCK_SIZE=8 ROW_FORMAT=COMPRESSED;

-- --------------------------------------------------------

--
-- Table structure for table `reporter_config`
--

DROP TABLE IF EXISTS `reporter_config`;
CREATE TABLE IF NOT EXISTS `reporter_config` (
  `rc_video_container_host` varchar(255) NOT NULL,
  `rc_video_container_localpath` varchar(255) NOT NULL,
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `rc_local_machine_container_path` varchar(1023) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `result`
--

DROP TABLE IF EXISTS `result`;
CREATE TABLE IF NOT EXISTS `result` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `re_result_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `re_result_name` (`re_result_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `run`
--

DROP TABLE IF EXISTS `run`;
CREATE TABLE IF NOT EXISTS `run` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `environment_id` bigint(20) NOT NULL,
  `team_id` bigint(20) NOT NULL,
  `r_run_uid` varchar(255) NOT NULL,
  `r_run_name` varchar(255) NOT NULL,
  `r_run_start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `r_run_finish_date` datetime DEFAULT NULL,
  `r_run_duration` bigint(20) DEFAULT NULL,
  `r_tests_count` bigint(20) DEFAULT 0,
  `r_passed_tests_count` bigint(20) DEFAULT 0,
  `r_failed_tests_count` bigint(20) DEFAULT 0,
  `r_skipped_tests_count` bigint(20) DEFAULT 0,
  `r_blocked_tests_count` bigint(20) DEFAULT 0,
  `r_errored_tests_count` bigint(20) DEFAULT 0,
  `r_build_version` longtext DEFAULT NULL,
  `r_is_developement_run` tinyint(4) NOT NULL DEFAULT 0,
  `r_is_run_finished` tinyint(4) NOT NULL DEFAULT 0,
  `test_type_id` bigint(20) NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  UNIQUE KEY `r_run_uid` (`r_run_uid`),
  KEY `r_run_name_idx` (`r_run_name`),
  KEY `r_run_start_date_idx` (`r_run_start_date`),
  KEY `r_run_finish_date_idx` (`r_run_finish_date`),
  KEY `r_run_duration_idx` (`r_run_duration`),
  KEY `fk_run_eid_idx` (`environment_id`),
  KEY `fk_run_tid_idx` (`team_id`),
  KEY `fk_run_ttid_idx` (`test_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Triggers `run`
--
DROP TRIGGER IF EXISTS `calculateRunDuration`;
DELIMITER $$
CREATE TRIGGER `calculateRunDuration` BEFORE UPDATE ON `run` FOR EACH ROW BEGIN
    IF (NEW.r_run_finish_date IS NOT NULL) THEN
        SET NEW.r_run_duration = TIMESTAMPDIFF(SECOND, NEW.r_run_start_date, NEW.r_run_finish_date);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `suite`
--

DROP TABLE IF EXISTS `suite`;
CREATE TABLE IF NOT EXISTS `suite` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `s_suite_uid` varchar(255) NOT NULL,
  `s_suite_name` varchar(255) NOT NULL,
  `s_suite_start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `s_suite_finish_date` datetime DEFAULT NULL,
  `s_suite_run_duration` bigint(20) DEFAULT NULL,
  `s_tests_count` bigint(20) DEFAULT 0,
  `s_passed_tests_count` bigint(20) DEFAULT 0,
  `s_failed_tests_count` bigint(20) DEFAULT 0,
  `s_skipped_tests_count` bigint(20) DEFAULT 0,
  `s_blocked_tests_count` bigint(20) DEFAULT 0,
  `s_errored_tests_count` bigint(20) DEFAULT 0,
  `s_run_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `s_suite_uid` (`s_suite_uid`),
  KEY `s_suite_name_idx` (`s_suite_name`),
  KEY `fk_suite_rid_idx` (`s_run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Triggers `suite`
--
DROP TRIGGER IF EXISTS `calculateSuiteDuration`;
DELIMITER $$
CREATE TRIGGER `calculateSuiteDuration` BEFORE UPDATE ON `suite` FOR EACH ROW BEGIN
    IF (NEW.s_suite_finish_date IS NOT NULL) THEN
        SET NEW.s_suite_run_duration=ROUND((select SUM(t_test_run_duration)/1000 from suite,test where suite.id=old.id and suite.id=t_suite_id),0);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

DROP TABLE IF EXISTS `team`;
CREATE TABLE IF NOT EXISTS `team` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tm_team_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tm_team_name` (`tm_team_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

DROP TABLE IF EXISTS `test`;
CREATE TABLE IF NOT EXISTS `test` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `feature_id` bigint(20) NOT NULL,
  `result_id` bigint(20) NOT NULL,
  `t_test_uid` varchar(255) NOT NULL,
  `t_test_name` varchar(255) NOT NULL,
  `t_testrail_id` varchar(255) DEFAULT NULL,
  `t_defect` varchar(255) DEFAULT NULL,
  `t_test_start_date` datetime NOT NULL,
  `t_test_finish_date` datetime NOT NULL,
  `t_test_run_duration` bigint(20) DEFAULT NULL,
  `t_additional_info` longtext DEFAULT NULL,
  `test_author_id` bigint(20) DEFAULT NULL,
  `t_test_video` varchar(255) DEFAULT NULL,
  `t_jira_id` varchar(255) DEFAULT NULL,
  `t_suite_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `t_test_uid` (`t_test_uid`),
  KEY `t_test_name_idx` (`t_test_name`),
  KEY `t_test_start_date_idx` (`t_test_start_date`),
  KEY `t_test_finish_date_idx` (`t_test_finish_date`),
  KEY `fk_test_fid_idx` (`feature_id`),
  KEY `fk_test_rid_idx` (`result_id`),
  KEY `fk_test_taid_idx` (`test_author_id`),
  KEY `fk_test_sid_idx` (`t_suite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Triggers `test`
--
DROP TRIGGER IF EXISTS `updateSuiteFinishDateTime`;
DELIMITER $$
CREATE TRIGGER `updateSuiteFinishDateTime` AFTER INSERT ON `test` FOR EACH ROW BEGIN
    UPDATE reporter.suite
    SET reporter.suite.s_suite_finish_date = NEW.t_test_finish_date
    WHERE reporter.suite.id = NEW.t_suite_id;
    SET @runId = (SELECT s_run_id FROM reporter.suite WHERE reporter.suite.`id` = NEW.`t_suite_id`);
    UPDATE reporter.run SET reporter.run.r_run_finish_date = NEW.t_test_finish_date WHERE reporter.run.`id` = @runId;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `testtype`
--

DROP TABLE IF EXISTS `testtype`;
CREATE TABLE IF NOT EXISTS `testtype` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tt_test_type_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tt_test_type_name` (`tt_test_type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `run`
--
ALTER TABLE `run` ADD FULLTEXT KEY `r_run_name_ftidx` (`r_run_name`);

--
-- Indexes for table `suite`
--
ALTER TABLE `suite` ADD FULLTEXT KEY `s_suite_name_ftidx` (`s_suite_name`);

--
-- Indexes for table `test`
--
ALTER TABLE `test` ADD FULLTEXT KEY `t_test_name_ftidx` (`t_test_name`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `fk_log_tid` FOREIGN KEY (`l_test_id`) REFERENCES `test` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `run`
--
ALTER TABLE `run`
  ADD CONSTRAINT `fk_run_eid` FOREIGN KEY (`environment_id`) REFERENCES `environment` (`id`),
  ADD CONSTRAINT `fk_run_tid` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`),
  ADD CONSTRAINT `fk_run_ttid` FOREIGN KEY (`test_type_id`) REFERENCES `testtype` (`id`);

--
-- Constraints for table `suite`
--
ALTER TABLE `suite`
  ADD CONSTRAINT `fk_suite_rid` FOREIGN KEY (`s_run_id`) REFERENCES `run` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `test`
--
ALTER TABLE `test`
  ADD CONSTRAINT `fk_test_fid` FOREIGN KEY (`feature_id`) REFERENCES `feature` (`id`),
  ADD CONSTRAINT `fk_test_rid` FOREIGN KEY (`result_id`) REFERENCES `result` (`id`),
  ADD CONSTRAINT `fk_test_sid` FOREIGN KEY (`t_suite_id`) REFERENCES `suite` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_test_taid` FOREIGN KEY (`test_author_id`) REFERENCES `author` (`id`);

DELIMITER $$
--
-- Events
--
DROP EVENT IF EXISTS `close_runs_in_progress`$$
CREATE DEFINER=`root`@`localhost` EVENT `close_runs_in_progress` ON SCHEDULE EVERY 3 MINUTE STARTS TIMESTAMP(NOW()+INTERVAL 1 MINUTE) ON COMPLETION NOT PRESERVE ENABLE DO call close_running$$

DROP EVENT IF EXISTS `remove_old_dev_runs`$$
CREATE DEFINER=`root`@`localhost` EVENT `remove_old_dev_runs` ON SCHEDULE EVERY 1 DAY STARTS CONCAT(DATE(NOW()+INTERVAL 1 DAY ), ' 00:00:00') ON COMPLETION NOT PRESERVE ENABLE DO call delete_old_runs(7,true)$$

DROP EVENT IF EXISTS `remove_old_logs`$$
CREATE DEFINER=`root`@`localhost` EVENT `remove_old_logs` ON SCHEDULE EVERY 1 DAY STARTS CONCAT(DATE(NOW()+INTERVAL 1 DAY ), ' 00:00:00') ON COMPLETION NOT PRESERVE ENABLE DO call delete_old_logs(30)$$

DELIMITER ;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
