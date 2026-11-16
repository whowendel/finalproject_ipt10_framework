<?php

// Fallback for servers without URL rewriting: send visitors to the real web root.
header('Location: public/', true, 302);
exit;
