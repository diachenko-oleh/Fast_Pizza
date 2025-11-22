<?php ?><!doctype html>
<html lang="uk">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'FAST PIZZA'; ?></title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
		<link rel="stylesheet" href="styles.css">
	</head>
	<body>
		<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
			<div class="container d-flex justify-content-center align-items-center">
				<ul class="navbar-nav d-flex flex-row align-items-center me-2">
					<li class="nav-item"><a class="nav-link px-2" href="index.php">Меню</a></li>
					<li class="nav-item"><a class="nav-link px-2" href="infopage.php">Інформація</a></li>
				</ul>

				<a class="navbar-brand mx-3 fs-4 fw-bold" href="index.php">FAST PIZZA</a>

				<ul class="navbar-nav d-flex flex-row align-items-center ms-2">
					<li class="nav-item"><a class="nav-link px-2" href="deliverypage.php">Доставка</a></li>
					<li class="nav-item"><a class="btn btn-outline-dark ms-2" href="cart.php">Кошик</a></li>
				</ul>
			</div>
			
			<a href="cart.php" class="floating-cart-btn">
    			Кошик
			</a>

		<script>
			document.addEventListener("scroll", () => {
    			const btn = document.querySelector(".floating-cart-btn");

    			if (window.scrollY > 150) {
        			btn.classList.add("show");
    			} else {
        			btn.classList.remove("show");
   				}
			});
		</script>
	</nav>