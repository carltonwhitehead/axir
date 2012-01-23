/*
Copyright 2012 Carlton Whitehead

This file is part of Autocross Instant Results.

Autocross Instant Results is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Autocross Instant Results is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Autocross Instant Results.  If not, see 
<http://www.gnu.org/licenses/>.
*/

create table `categories`
(
    `id` integer primary key autoincrement,
    `name` text not null,
    `label` text not null,
    `prefix` text not null,
    `is_raw` integer not null,
    `is_pax` integer not null
);
insert into `categories` (`name`, `label`, `prefix`, `is_raw`, `is_pax`) values
    ('open', 'Open', '', 1, 0);
insert into `categories` (`name`, `label`, `prefix`, `is_raw`, `is_pax`) values
    ('pro', 'Pro', 'X', 0, 1);
insert into `categories` (`name`, `label`, `prefix`, `is_raw`, `is_pax`) values
    ('lad', 'Ladies', 'LAD', 0, 1);
insert into `categories` (`name`, `label`, `prefix`, `is_raw`, `is_pax`) values
    ('tir', 'Street Tire', 'TIR', 0, 1);
insert into `categories` (`name`, `label`, `prefix`, `is_raw`, `is_pax`) values
    ('nov', 'Novice', 'NOV', 0, 1);
insert into `categories` (`name`, `label`, `prefix`, `is_raw`, `is_pax`) values
    ('of', 'Old Farts', 'OF', 0, 1);
insert into `categories` (`name`, `label`, `prefix`, `is_raw`, `is_pax`) values
    ('to', 'Time Only', 'TO', 1, 0);

create table classes
(
    `id` integer primary key autoincrement,
    `name` text
);

create table events
(
    `id` integer primary key autoincrement,
    `file` text not null,
    `file_modified` date not null,
    `date` date not null,
    `label` text not null,
    `cone_seconds` float not null
);
create unique index unique_event_file on events (`file`);

create table runs
(
    `id` integer primary key autoincrement,
    `event_id` integer not null references events (id),
    `driver_id` integer not null references drivers (id),
    `number` integer not null,
    `time_raw` float not null,
    `time_pax` float not null,
    `penalty` text not null,
    `time_raw_with_penalty` float not null,
    `time_pax_with_penalty` float not null,
    `diff` text,
    `diff_from_first` text,
    `timestamp` datetime not null
);

create table `drivers`
(
    `id` integer primary key autoincrement,
    `event_id` integer references events (id),
    `category_id` integer references categories (id),
    `class_id` integer references class (id),
    `number` text not null,
    `name` text not null,
    `car` text not null,
    `car_color` text not null,
    `best_time_raw` float,
    `best_time_raw_run_id` integer references runs (id),
    `best_time_pax` float,
    `best_time_pax_run_id` integer references runs (id),
    constraint unique_event_driver unique (`event_id`, `category_id`, `class_id`, `number`) on conflict ignore
);
create index idx_event_categories_classes on drivers (`event_id`, `category_id`, `class_id`);

create trigger drivers_after_delete after delete on drivers
begin
    delete from runs
    where
        runs.driver_id = old.id
    ;
end;

create trigger runs_after_insert after insert on runs
begin
    update drivers set
        best_time_raw = new.time_raw_with_penalty,
        best_time_raw_run_id = new.id,
        best_time_pax = new.time_pax_with_penalty,
        best_time_pax_run_id = new.id
    where
        id = new.driver_id
        and
        (
            (
                best_time_raw is null 
                and best_time_pax is null
            )
            or
            (
                new.time_raw_with_penalty < best_time_raw
                and new.time_pax_with_penalty < best_time_pax
            )
        )
    ;
end;

create trigger events after delete on events
begin
    delete from drivers where drivers.event_id = old.id;
end;
