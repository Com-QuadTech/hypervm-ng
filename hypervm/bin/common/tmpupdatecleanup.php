<?php 
include_once "htmllib/lib/include.php";
include_once "lib/updatelib.php";
include_once "htmllib/lib/updatelib.php";

exit_if_another_instance_running();
debug_for_backend();
updatecleanup_main();

function updatecleanup_main()
{
	global $argc, $argv;
	global $gbl, $sgbl, $login, $ghtml; 

	$program = $sgbl->__var_program_name;
	$opt = parse_opt($argv);

	if ($opt['type'] === 'master') {
		initProgram('admin');
		$flg = "__path_program_start_vps_flag";
		if (!lxfile_exists($flg)) {
			set_login_skin_to_feather();
		}
	} else {
		$login = new Client(null, null, 'update');
	}

	print("Executing UpdateCleanup. This can take a long time. Please be patient\n");
	log_log("update", "Executing Updatecleanup");

//
// Cleanup old lxlabs.repo file
// 
print("Fixing Repo's\n");
	if (lxfile_exists("/etc/yum.repos.d/lxcenter.repo")) {
	if (lxfile_exists("/etc/yum.repos.d/lxlabs.repo")) {
		lxfile_mv("/etc/yum.repos.d/lxlabs.repo", "/etc/yum.repos.d/lxlabs.repo.lxsave");
		system("rm -f /etc/yum.repos.d/lxlabs.repo");
		}
		}
	if (lxfile_exists("CVS")) {
		print("Found Development version, we just go on.\n");
//		exit;
	}

	if ($opt['type'] === 'master') {
		$sgbl->slave = false;
		if (!is_secondary_master()) {
			print("Update database\n");
			updateDatabaseProperly();
			print("Fix Extra database issues\n");
			fixExtraDB();
			print("Update extra issues\n");
			doUpdateExtraStuff();
			print("Get Driver info\n");
			lxshell_return("__path_php_path", "../bin/common/driverload.php");
		}
		print("Starting Update all slaves\n");
		update_all_slave();
		print("Fix main $program databasefile\n");
		cp_dbfile();
	} else {
		$sgbl->slave = true;
	}

	if (!is_secondary_master()) {
		print("Starting update cleanups\n");
		updatecleanup();
	}

	lxfile_touch("__path_program_start_vps_flag");
}

function cp_dbfile()
{
	global $gbl, $sgbl, $login, $ghtml;

	$progname = $sgbl->__var_program_name;

	lxfile_cp("../sbin/{$progname}db", "/usr/bin/{$progname}db");
	lxfile_generic_chmod("/usr/bin/{$progname}db", "0755");
}

