<?php require_once("../includes/session.php"); ?>
<?php require_once("../includes/functions.php"); ?>
<?php require_once("../includes/db_connection.php"); ?>
<?php confirm_logged_in_as_korisnik(); ?>
<?php include("../includes/layouts/header.php"); ?>
<?php
$logovani_korisnik = $_SESSION["username"];
$korisnik_ime_i_prezime = find_ime_i_prezime_logovanog_korisnika($logovani_korisnik);
$ime_i_prezime = mysqli_fetch_assoc($korisnik_ime_i_prezime);
?>
<?php
$id_task = $_GET["id_task"];
$task_set = find_task_by_id($id_task);
$task = mysqli_fetch_assoc($task_set);
?>
<!-- dohvatamo sve vrste statusa taska (da bi ih prikazali u dropdown listi -->
<?php $statusi_set = find_all_task_statusi(); ?>
<?php $task_history_set = find_task_history($id_task); ?>
<?php $task_komentari_set = find_task_komentari($id_task); ?>
<?php $task_mailovi_set = find_task_mail_list_korisnik($id_task); ?>

<?php
if (isset($_POST['submit']))
{

	$stat = (int)$_POST['status'];

	//$_SESSION["message"] = $oper.' '.$stat  ;//PROVJERA

		// pozivamo funkciju za update taska od strane admina
		$update_result = korisnik_update_task($stat,$id_task);

		if ($update_result && mysqli_affected_rows($connection) >= 0)
		{

			$user_izmjenio = $ime_i_prezime["ime_prezime"];

			$task_after_update_set = find_task_by_id($id_task);
			$task_after_update = mysqli_fetch_assoc($task_after_update_set);

		  $akcija = 'IZMJENA TASKA | status : '.$task_after_update["status"].' | operater : '.$task_after_update["ime_operatera"];

			// insert u tabelu historije
			$insert_ist  = "INSERT INTO task_istorija (";
			$insert_ist .= "  akcija, id_task, logovani_user, vrijeme_izmjene";
			$insert_ist .= ") VALUES (";
			$insert_ist .= "  '{$akcija}', '{$id_task}', '{$user_izmjenio}', now() ";
			$insert_ist .= ")";
			$insert_result = mysqli_query($connection, $insert_ist);

			// slanje maila prema korisniku, operateru i adminu da je nesto izmjenjeno
			// ovo su aderse na koje treba da ide mail o izmjeni taska
				while($mailovi = mysqli_fetch_array($task_mailovi_set))
				{
				    $adrese[] = $mailovi["email"];
				}
				$to = implode(", ", $adrese);

				$subject = "IZMJENA TASKA OD STRANE KORISNIKA";

		   	$message  = "----------------------------------------------------------------------------------------------------------------------";
				$message .= '<br /><br />';
				$message .= "MOLIMO DA NE ODGOVARATE NA OVU PORUKU PUTEM MAILA !!!";
				$message .= '<br /><br />';
				$message .= "----------------------------------------------------------------------------------------------------------------------";
				$message .= '<br /><br />';
				$message .= 'IZMJENA TASKA';
				$message .= '<br /><br />';
				$message .= 'task id : ' .$id_task;
				$message .= '<br />';
				$message .= 'status : ' .$task_after_update["status"];
				$message .= '<br />';
				$message .= 'operater : '.$task_after_update["ime_operatera"];
				$message .= '<br /><br />';
				$message .= "PRIJAVITE SE NA APLIKACIJU : ";
				$message .= '<a href = "http://178.62.252.220/aplikacija/public/login.php">KLIKNITE OVDJE</a>';
				$message .= '<br /><br />';

				$headers  = 'From: taskmaster@mail.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
				$headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

				mail($to,$subject,$message,$headers);

			// AUTO REFRESH
			$url1=$_SERVER['REQUEST_URI'];
      header("Refresh: 1.5; URL=$url1");

			// Success
		  $_SESSION["message_level"] = "green";
			$_SESSION["message"] = "Izmjene su uspješno izvršene.";

		}
		else
		{
			// AUTO REFRESH
			$url1=$_SERVER['REQUEST_URI'];
      header("Refresh: 1.5; URL=$url1");

			// Failure
		  $_SESSION["message_level"] = "red";
			$_SESSION["message"] = "Došlo je do greške !!!";

		}

}
else {
  // This is probably a GET request

} // end: if (isset($_POST['submit']))
?>



<div class="container">

	<header id="navtop">
		<a href="korisnik.php" class="logo fleft">
			<img src="img/logo2.png" id="logoimg">
			<div class="fleft grid__item color-9">
				<a class="link link--ilin" href="korisnik.php"><span>TASK</span><span>MASTER</span></a>
			</div>
		</a>
		<nav class="main-nav fright cl-effect-5">
			<ul>
				<!-- linkovi koji pozivaju modal -->
				<li>
					<a href="#0"
					   class="dr-icon dr-icon-lock2 cd-reset ">
					   <span data-hover="Promjena lozinke">Promjena lozinke</span>
					</a>
				</li>
			</ul>
    <!-- kod liste iznad, li > a je dobio klasu cd-reset > zove modal za password -->
	<!-- kod liste ispod, li > a > span je dobio klasu cd-contact -->
	<!-- jer ako dodijelim klasu cd-contact elementu li > a	-->
	<!-- onda ne zna da presalta tab sa prvog na drugi (password na kontakt -->
	<!-- li iz prvog ul-a mora biti po strukturi drugaciji od li iz drugog ul-a -->
			<ul>
				<!-- linkovi koji pozivaju modal -->
				<li>
				    <a href="#0"
					class="dr-icon dr-icon-mail" >
					<span class="cd-contact" data-hover="Kontakt">Kontakt</span>
					</a>
				</li>
			</ul>
			<ul>
				<li>
					<a href="logout.php"
					   class="cd-contact dr-icon dr-icon-switch">
					   <span data-hover="Logout">Logout</span>
					</a>
				</li>
			</ul>
		</nav>
	</header>


	<!-- modal -->
	<div class="cd-user-modal"> <!-- modalni prozor za promjenu passworda ili slanje maila -->
		<div class="cd-user-modal-container"> <!-- modalov kontejner -->
			<ul class="cd-switcher">
				<li><a href="#0">Promjena lozinke</a></li>
				<li><a href="#0">Kontakt</a></li>
			</ul>

			<div id="cd-reset"> <!-- reset password forma -->
				<form class="cd-form" id="reset_pass" name="reset_pass" action="korisnik.php" method="post">
					<p class="fieldset">
						<label class="normaltext">
                        Da biste promijenili Vašu lozinku, potrebno je da
						unesete Vašu trenutno aktivnu lozinku, zatim unesete
                        novu lozinku i zatim ponovite unos nove lozinke
						i kliknete na 'SPASI'.
					    Promjenom lozinke s Vaše strane postajete jedina osoba koja zna lozinku
						jer se lozinka šifrira i čuva u bazi u šifriranom obliku tako da niko ne može pročitati istu.
						</label>
					</p>
					<p class="fieldset">
						<label class="image-replace cd-password" for="signin-password">Password</label>
						<input class="full-width has-padding has-border"
						       id="signin-password"
						       name="signin-password"
							   type="text"
							   placeholder="Trenutna lozinka">
						<a href="#0" class="hide-password">Sakrij</a>
					</p>

					<p class="fieldset">
						<label class="image-replace cd-password" for="signin-password">Password</label>
						<input class="full-width has-padding has-border"
						       id="signin-password-new"
						       name="signin-password-new"
							   type="text"
							   placeholder="Nova lozinka">
						<a href="#0" class="hide-password">Sakrij</a>
					</p>

					<p class="fieldset">
						<label class="image-replace cd-password" for="signin-password">Password</label>
						<input class="full-width has-padding has-border"
						       id="confirm_password"
						       name="confirm_password"
							   type="text"
							   placeholder="Ponovi novu lozinku">
						<a href="#0" class="hide-password">Sakrij</a>
					</p>


	                     <button type="submit" name="reset-submit"
						         value="Spasi"
								 style="font-size:28px;"
								 class="btn btn-lg
								 btn-block
								 btn-primary
								 dr-icon
								 dr-icon-check"
								> SPASI
						</button>

				</form>
			</div> <!-- cd-reset -->

			<div id="cd-contact"> <!-- sign up form -->
				<form class="cd-form" name="contactform" id="contactform" action="contact-us.php" method="post">
					<p class="fieldset">
						<label class="normaltext">
						Ukoliko imate bilo kakvih dodatnih pitanja, stojimo Vam na raspolaganju. Popunite formular ispod i Vaša poruka će biti proslijeđena našem glavnom administratoru.
						Odgovor ćete dobiti na dole navedenu e-mail adresu.
						</label>
					</p>
					<p class="fieldset">
						<label class="image-replace cd-username" for="signup-name">
						Ime i prezime</label>
						<input class="full-width has-padding has-border"
							   id="signup-name"
							   name="signup-name"
							   type="text" placeholder="Unesite svoje ime i prezime">
					</p>
					<p class="fieldset">
						<label class="image-replace cd-email" for="signup-email">E-mail</label>
						<input class="full-width has-padding has-border"
						       id="signup-email"
							   name="signup-email"
							   type="email"
							   placeholder="Unesite Vaš e-mail">
					</p>
					<p class="fieldset">
						<label for="signup-message" style="font-size:18px;">
						Unesite vašu poruku</label>
		    <textarea id="signup-message" name="signup-message" class="full-width has-padding has-border"></textarea>
					</p>
	                     <button type="submit" name="contact-submit" id="contact-submit"
						         value="Spasi"
								 style="font-size:28px;"
								 class="btn btn-lg
								 btn-block
								 btn-primary
								 dr-icon
								 dr-icon-check"
								> posalji
						</button>
				</form>
			</div> <!-- cd-contact -->

		</div> <!-- cd-user-modal-container -->
	</div> <!-- cd-user-modal -->
	<!-- modal -->


	<div class="container">
	    <div class="style-nav"></div>
        </br>

		<?php
		// ako je izmjenjen status ili operater uspješno
		// izbaci alert-success (zeleni message koji se pojavi nakon redirecta)
			if (has_presence($_SESSION["message"])	)
			{
				if ($_SESSION["message_level"] === "red")
				{
					 echo
						'<div class="alert alert-dismissible alert-danger">
						<button type="button" class="close" data-dismiss="alert">×</button>'
						;
					 echo  message();
					 echo  '</div>';
				}
				else if ($_SESSION["message_level"] === "green")
				{
					 echo
						'<div class="alert alert-dismissible alert-success">
						<button type="button" class="close" data-dismiss="alert">×</button>'
						;
					 echo  message();
					 echo  '</div>';
				}
			 }
		?>

		<ul class="nav nav-tabs">
		  <li class="active"><a href="#fiksno" data-toggle="tab" aria-expanded="true">PODACI O TASKU</a></li>
		  <li class=""><a href="#promjenjivo" data-toggle="tab" aria-expanded="false">IZMJENE TASKA</a></li>
		  <li class=""><a href="#istorija" data-toggle="tab" aria-expanded="false">ISTORIJA TASKA</a></li>
		  <li class=""><a href="#komentari" data-toggle="tab" aria-expanded="false">KOMENTARI TASKA</a></li>
		</ul>
        </br>
		<div id="myTabContent" class="tab-content">
			<div class="tab-pane fade active in" id="fiksno">
				<?php
                if (!empty($task["id_status"]) && ($task["id_status"] ==4))
				{
				echo '<div class="panel panel-danger">';
				}
				else if (!empty($task["id_status"]) && ($task["id_status"] ==5))
				{
				echo '<div class="panel panel-success">';
				}
				else
				{
				echo '<div class="panel panel-primary">';
				}
				?>
				  <div class="panel-heading">
					<h3 class="panel-title">TASK ID <?php echo $task["id_task"]; ?>	</h3>
				  </div>
				  <div class="panel-body"></p>
				  	<h5>STATUS TASKA : <?php echo $task["status"]; ?>	</h5>
					<?php
					    if (!empty($task["id_status"]) && ($task["id_status"] ==1))
								{
								echo '  <div class="progress">
									        <div class="progress-bar" style="width: 20%;"></div>
									    </div>';
								}
					    else if (!empty($task["id_status"]) && ($task["id_status"] ==2))
								{
								echo '  <div class="progress">
									        <div class="progress-bar" style="width: 40%;"></div>
									    </div>';
								}
					    else if (!empty($task["id_status"]) && ($task["id_status"] ==3))
								{
								echo '  <div class="progress">
									        <div class="progress-bar" style="width: 50%;"></div>
									    </div>';
								}
					    else if (!empty($task["id_status"]) && ($task["id_status"] ==4))
								{
								echo '  <div class="progress">
									        <div class="progress-bar progress-bar-danger" style="width: 100%;"></div>
									    </div>';
								}
					    else if (!empty($task["id_status"]) && ($task["id_status"] ==5))
								{
								echo '  <div class="progress">
									        <div class="progress-bar progress-bar-success" style="width: 100%;"></div>
									    </div>';
								} ;
					?>
					</BR>
					<h5 style="margin:0px;">TASK INFO</h5>
				    <div class="style-thick" style="margin-bottom:4px;"></div>
					NASLOV : <?php echo $task["naslov"]; ?>
					<div class="style-data"></div>
					TIP UREĐAJA : <?php echo $task["tip_uredjaja"]; ?>
					<div class="style-data"></div>
					DODATNA OPREMA : <?php echo $task["dodatna_oprema"]; ?>
					<div class="style-data"></div>
					PN (product number) : <?php echo $task["pn_broj"]; ?>
					<div class="style-data"></div>
					SN (serial number) : <?php echo $task["sn_broj"]; ?>
					<div class="style-data"></div>
					OPIS PROBLEMA : <?php echo $task["opis_problema"]; ?>
					<div class="style-data"></div>
					POČETAK RADA : <?php echo '<span style="color:blue;">'.date('d-m-Y H:i:s', strtotime($task["task_start"])).'</span>'; ?>
					<div class="style-data"></div>
					ROK ZA IZRADU :
					<?php if (empty($task["task_rok"]))
								{
								echo "Administrator još nije odredio predviđeni rok završetka !!!";
								}
							else
								{
								echo '<span style="color:red;">';
								echo(date('d-m-Y', strtotime($task["task_rok"])));
								echo '</span>';
								}
					?>
					<div class="style-data"></div>
					KRAJ RADA :
					<?php if (empty($task["task_end"]))
								{
								echo "Datum završetka rada bit će naveden kada posao bude završen !!!";
								}
							else
								{
								echo '<span style="color:green;">';
								echo $task["task_end"];
								echo '</span>';
								}
					?>
					<div class="style-data"></div>

					</BR>

					<h5 style="margin:0px;">KORISNIK</h5>
				    <div class="style-thick" style="margin-bottom:4px;"></div>
					IME I PREZIME : <?php echo $task["ime_korisnika"]; ?>
					<div class="style-data"></div>
					USERNAME : <?php echo $task["username_korisnika"]; ?>
					<div class="style-data"></div>
					TELEFON : <?php echo $task["korisnik_telefon"]; ?>
					<div class="style-data"></div>
					EMAIL: <?php echo $task["korisnik_email"]; ?>
					<div class="style-data"></div>

					</BR>

					<h5 style="margin:0px;">OPERATER</h5>
				    <div class="style-thick" style="margin-bottom:4px;"></div>
					IME I PREZIME: <?php echo $task["ime_operatera"]; ?>
					<div class="style-data"></div>
					USERNAME : <?php echo $task["username_operatera"]; ?>
					<div class="style-data"></div>
					EMAIL : <?php echo $task["email_operatera"]; ?>
					<div class="style-data"></div>

				  </div>
				</div>
			</div>
			<div class="tab-pane fade" id="promjenjivo">
				<div class="panel panel-success">
				  <div class="panel-heading">
					<h3 class="panel-title">TASK ID <?php echo $task["id_task"]; ?> : IZMJENE</h3>
				  </div>
				  <div class="panel-body">

						<form id="novi_task" action="" method="post" name="task_update">
							<ul>
								<li>
									<label for="status">Aktuelni status taska</label>
									<select name="status">
										<option>
											<?php  echo $task["id_status"].' '.$task["status"]; ?>
										</option>
											<?php
												while($status = mysqli_fetch_assoc($statusi_set))
												{
													echo '<option>';
													echo htmlentities($status["id_status"]);
													echo ' ';
													echo htmlentities($status["status"]);
													echo '</option>';
												}
											?>
									</select>
								</li>

								<li>
									<button type="submit" id="submit" name="submit"
											style="font-size:28px;"
											class="submit
												   btn btn-lg btn-block btn-success
												   dr-icon dr-icon-floppy-disk"
												   > &nbsp;Spasi
									</button>
								</li>
							</ul>
						</form>

				  </div>
				</div>
			</div>
			<div class="tab-pane fade" id="istorija">
					<div class="panel panel-operater">
					  <div class="panel-heading">
						<h3 class="panel-title">TASK ID <?php echo $task["id_task"]; ?> : ISTORIJA IZMJENA</h3>
					  </div>
					  <div class="panel-body">

              <?php while($task_history = mysqli_fetch_assoc($task_history_set)) { ?>
							<?php
							  echo '<div class="style-data"></div>';
								echo '<span style="text-transform:uppercase;color: #5A6F9E;">'.$task_history["logovani_user"].' : '.date('d-m-Y H-i-s', strtotime($task_history["vrijeme_izmjene"]));
								echo '</span></br>';
								echo $task_history["akcija"];
							?>
						<?php }; ?>
						<div class="style-data"></div>
					  </div>
					</div>
			</div>

			<div class="tab-pane fade" id="komentari">

				<?php while($task_komentari = mysqli_fetch_assoc($task_komentari_set)) { ?>
					<?php
					IF ($task_komentari["id_komentar"] % 2 == 0)
					{
					    echo '<div class="alert alert-grey">';
                        echo '<strong style="text-transform:uppercase;">';
						echo $task_komentari["logovani_user"].' : '.date('d-m-Y H-i-s', strtotime($task_komentari["datum_i_vrijeme"]));
						echo '</strong>';
						echo '</br>';
						echo $task_komentari["komentar"];
					    echo '</div>';
					}
					ELSE
					{
					    echo '<div class="alert alert-light">';
                        echo '<strong style="text-transform:uppercase;">';
						echo $task_komentari["logovani_user"].' : '.date('d-m-Y H-i-s', strtotime($task_komentari["datum_i_vrijeme"]));
						echo '</strong>';
						echo '</br>';
						echo $task_komentari["komentar"];
					    echo '</div>';
					}
					?>
				<?php }; ?>

				<form class="form-horizontal" id="dodaj_komentar" action="dodaj_komentar_kao_korisnik.php" method="get" name="dodaj_komentar">
				  <fieldset>
					<legend>DODAJ KOMENTAR</legend>

					<div class="form-group">
					  <div class="col-lg-12">
						<textarea rows="3" id="komentar" name ="komentar"></textarea>
					  </div>
					  <div class="col-lg-12">
						<input type="hidden" name="id_task" id="id_task" value="<?php echo $task["id_task"]; ?>" >
					  </div>
					</div>

					<div class="form-group">
					  <div class="col-lg-12">
						<button type="komentar-submit" style="font-size:28px;" class="btn btn-lg btn-block btn-primary dr-icon dr-icon-chat">&nbsp;&nbsp;DODAJ KOMENTAR</button>
					  </div>
					</div>
				  </fieldset>
				</form>

			</div>
		</div>

	</div> <!--main-->

	</br>
	</br>
	</br>

    <div class="style-nav"></div>
			<p class="fright dr-icon dr-icon-user">
				<?php echo $ime_i_prezime["ime_prezime"] ?>
				: <span class="blue">admin</span>
			</p>
</div>



<!-- footer -->
<?php include("../includes/layouts/footer.php"); ?>
