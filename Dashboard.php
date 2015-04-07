<?php namespace Dashboard;

class Dashboard
{
	private $__connection;
	private $__posts                  = [];
	private $__totalPosts             = 0;
	private $__sixHourPostTotal       = 0;
	private $__totalMembers           = 0;
	private $__totalSubscribers       = 0;
	private $__totalCompletedProfiles = 0;

	public function __construct()
	{
		$this->__connection = new \PDO("mysql:host=172.31.37.84;dbname=up", 'up', 'FUNNYBONE');

		$this->__load();
	}

	public function changePassword($userIk, $newPassword)
	{
		foreach ($this->__connection->query("SELECT salt FROM user WHERE ik = $userIk") AS $row)
		{
			$password = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 11, 'salt' => $row[0]]);
		}

		$this->__connection->exec("UPDATE user SET password = '$password' WHERE ik = $userIk LIMIT 1");
	}

	public function getConnection()
	{
		return $this->__connection;
	}

	public function getRecentPosts()
	{
		return $this->__posts;
	}

	public function getSixHourPostTotal()
	{
		return $this->__sixHourPostTotal;
	}

	public function getTotalPosts()
	{
		return $this->__totalPosts;
	}

	public function getTotalMembers()
	{
		return $this->__totalMembers;
	}

	public function getTotalSubscribers()
	{
		return $this->__totalSubscribers;
	}

	public function getTotalCompletedProfiles()
	{
		return $this->__totalCompletedProfiles;
	}

	public function getMostRecentSubscribers($limit = 10)
	{
		if (!is_int($limit))
		{
			$limit = 10;
		}

		$new = [];
		foreach ($this->__connection->query("SELECT email, date(registered), name, if(custom_background is null or custom_background = '', 0, 1) AS complete, user_name FROM user ORDER BY registered DESC LIMIT $limit") AS $row)
		{
			$email = $row[ 0 ];
			$new[ ] = [
				'email'    => $email,
				'date'     => $row[ 1 ],
				'name'     => $row[ 2 ],
				'complete' => $row[ 3 ],
				'username' => $row[ 4 ]
			];
		}

		return $new;
	}

	public function getMostRecentNewsletterSubscribers($limit = 10)
	{
		if (!is_int($limit))
		{
			$limit = 10;
		}

		$new = [];
		foreach ($this->__connection->query("SELECT m.email, max(date(subscribed)) AS subscribed, if(u.ik is null, 0, 1) AS member FROM marketing m LEFT OUTER JOIN user u ON u.email = m.email GROUP BY m.email ORDER BY subscribed DESC LIMIT $limit") AS $row)
		{
			$email = $row[ 0 ];
			$new[ ] = [
				'email'    => $email,
				'date'     => $row[ 1 ],
				'isMember' => $row[ 2 ]
			];
		}

		return $new;
	}

	public function getSubscribersByDay()
	{
		$result = [];

		$date = new \DateTime('2014-07-13');
		$today = new \DateTime();
		$total = 0;

		foreach ($this->__connection->query("SELECT date(registered), count(*) FROM user GROUP BY date(registered) ORDER BY registered") AS $row)
		{
			$checkDate = $date->format('Y-m-d');

			//Let's not skip days...
			while ($checkDate != $row[ 0 ])
			{
				$result[ ] = [
					'total'  => $total,
					'period' => $checkDate,
					'new'    => 0
				];

				$date->add(new \DateInterval('P1D'));
				$checkDate = $date->format('Y-m-d');
			}

			$result[ ] = [
				'total'  => ($total + $row[ 1 ]),
				'period' => $row[ 0 ],
				'new'    => $row[ 1 ]
			];
			$total += $row[ 1 ];

			$date->add(new \DateInterval('P1D'));
		}

		if ($checkDate != $today->format('Y-m-d'))
		{
			while ($today > $date)
			{
				$result[ ] = [
					'total'  => $total,
					'period' => $date->format('Y-m-d'),
					'new'    => 0
				];

				$date->add(new \DateInterval('P1D'));
			}
		}

		return $result;
	}

	public function removePost($ik)
	{
		if (is_numeric($ik))
		{
			$ik = (int)$ik;

			$this->__connection->exec("UPDATE post SET indexed = 0, visible = 0, status = 'deleted' WHERE ik = $ik LIMIT 1");
		}
	}

	protected function __load()
	{
		$this->__getPosts();
		$this->__getMembers();
		$this->__getSubscribers();
	}

	protected function __getPosts()
	{
		$this->__posts = [];
		foreach ($this->__connection->query("SELECT * FROM (SELECT p.title, p.id, p.ik, p.description, u.name, p.user_ik, p.status, if(created >= date_sub(current_date, INTERVAL 6 HOUR), 1, 0) AS recent, u.email, p.created FROM up.post p JOIN up.user u on u.ik = p.user_ik WHERE user_ik <> 3 ORDER BY ik DESC LIMIT 100) d WHERE status = 'posted'") AS $row)
		{
			$this->__posts[ ] = [
				'title'       => $row[ 0 ],
				'thumb'       => sprintf('http://i.upcyclepost.com/post/%s-%s.small.png', $row[ 1 ], $row[ 2 ]),
				'description' => $row[ 3 ],
				'user'        => $row[ 4 ],
				'ik'          => $row[ 2 ],
				'email'       => $row[ 8 ],
				'created'     => $row[ 9 ]
			];

			if ($row[ 7 ] == 1)
			{
				$this->__sixHourPostTotal++;
			}
		}

		$this->__totalPosts = 0;
		foreach ($this->__connection->query("SELECT count(*) from post WHERE status = 'posted'") AS $row)
		{
			$this->__totalPosts = $row[ 0 ];
		}
	}

	protected function __getSubscribers()
	{
		$this->__totalSubscribers = 0;
		foreach ($this->__connection->query("SELECT count(distinct email) from marketing") AS $row)
		{
			$this->__totalSubscribers = $row[ 0 ];
		}
	}

	protected function __getMembers()
	{
		$this->__totalMembers = 0;
		foreach ($this->__connection->query("SELECT count(*) from user") AS $row)
		{
			$this->__totalMembers = $row[ 0 ];
		}

		$this->__totalCompletedProfiles = 0;
		foreach ($this->__connection->query("SELECT count(*) from user WHERE (custom_background IS NOT NULL AND custom_background <> '')") AS $row)
		{
			$this->__totalCompletedProfiles = $row[ 0 ];
		}
	}
}