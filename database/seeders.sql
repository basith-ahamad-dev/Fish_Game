-- Required seed data: achievement badges and the initial administrator.
-- Import after schema.sql. Admin login: admin / Admin@12345 — change this password immediately.

INSERT INTO badges (id, name, description, icon, requirement_type, requirement_value, sort_order) VALUES
(1, 'First Catch', 'Complete your first game.', 'hook', 'games_played', 1, 1),
(2, 'Beginner Angler', 'Complete 10 games.', 'fish', 'games_played', 10, 2),
(3, 'Sharp Angler', 'Score 1,000 points or more in a single game.', 'target', 'best_score', 1000, 3),
(4, 'Hot Streak', 'Win 5 games in a row.', 'flame', 'win_streak', 5, 4),
(5, 'Champion', 'Reach the global top 10.', 'crown', 'global_rank', 10, 5),
(6, 'Elite Angler', 'Reach level 10.', 'star', 'level', 10, 6),
(7, 'Speed Fisher', 'Win a game on Hard difficulty.', 'bolt', 'hard_wins', 1, 7),
(8, 'Ocean Master', 'Complete 100 games.', 'wave', 'games_played', 100, 8);

INSERT INTO users (id, username, email, password, role, bio) VALUES
(1, 'admin', 'admin@deepcatch.local', '$2y$10$5eiUziLg.Kxz7p.XARxd1e2qo5rRp.deO3WUTmZf0NtmxT4ktqJXq', 'admin', 'Keeper of the reef.');
