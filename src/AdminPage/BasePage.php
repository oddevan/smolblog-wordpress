<?php

namespace Smolblog\WP\AdminPage;

use Formr\Formr;

class BasePage implements AdminPage {
	public static function getConfiguration(): AdminPageConfiguration {
		return new AdminPageConfiguration(
			key: 'smolblog',
			pageTitle: 'Smolblog',
			menuTitle: 'Smolblog',
			wp_icon: 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJMYXllcl8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDIwMDAgMTUwMCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMjAwMCAxNTAwOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PHN0eWxlIHR5cGU9InRleHQvY3NzIj4uc3Qwe2ZpbGw6Izg4RDQ5MTt9PC9zdHlsZT48Zz48cGF0aCBjbGFzcz0ic3QwIiBkPSJNMTMzMy4zLDE2Ni43Yy0zMTEuMywwLTU3MiwyMTMuNy02NDUuNCw1MDIuMWM4MS45LDEwLjYsMTQ1LjQsNzkuOCwxNDUuNCwxNjQuNWMwLDkyLTc0LjYsMTY2LjctMTY2LjcsMTY2LjdTNTAwLDkyNS40LDUwMCw4MzMuM3M3NC42LTE2Ni43LDE2Ni43LTE2Ni43di01MDBDMjk4LjUsMTY2LjcsMCw0NjUuMSwwLDgzMy4zUzI5OC41LDE1MDAsNjY2LjcsMTUwMGMzMTEuMywwLDU3Mi0yMTMuNyw2NDUuNC01MDIuMWMtODEuOS0xMC42LTE0NS40LTc5LjgtMTQ1LjQtMTY0LjVjMC05Miw3NC42LTE2Ni43LDE2Ni43LTE2Ni43YzkyLDAsMTY2LjcsNzQuNiwxNjYuNywxNjYuN3MtNzQuNiwxNjYuNy0xNjYuNywxNjYuN3Y1MDBjMzY4LjIsMCw2NjYuNy0yOTguNSw2NjYuNy02NjYuN1MxNzAxLjUsMTY2LjcsMTMzMy4zLDE2Ni43eiBNNDE2LjcsNTAwYy00NiwwLTgzLjMtMzcuMy04My4zLTgzLjNjMC00NiwzNy4zLTgzLjMsODMuMy04My4zczgzLjMsMzcuMyw4My4zLDgzLjNDNTAwLDQ2Mi43LDQ2Mi43LDUwMCw0MTYuNyw1MDB6Ii8+PHBhdGggY2xhc3M9InN0MCIgZD0iTTc1MCw4My4zYzAtNDYtMzcuMy04My4zLTgzLjMtODMuM3YxNjYuN0M3MTIuNywxNjYuNyw3NTAsMTI5LjQsNzUwLDgzLjN6Ii8+PC9nPjwvc3ZnPg==',
			position: 3,
		);
	}

	public function __construct(private Formr $form) {}

	public function handleForm(): void {
		// get our form values and assign them to a variable
    $data = $this->form->validate('Name, Email, Comments');

    // show a success message if no errors
    if($this->form->ok()) {
        $this->form->success_message = "Thank you, {$data['name']}!";
    }
	}

	public function displayPage(): void {
		echo '<p>The future of blogging awaits! This ain\'t it, though.</p>';

		$this->form->action = '';
		$this->form->fastform([
			'text' => 'name,Name,John Wick',
			'email' => 'email,Email,johnwick@gunfu.com',
			'textarea' => 'comments,Comments'
		]);
		
	}
}