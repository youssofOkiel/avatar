<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_without' => 'حقل :attribute مطلوب عندما لا يكون :values موجودًا.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا يكون أي من :values موجودًا.',
    'string' => 'يجب أن يكون :attribute نصًا.',
    'integer' => 'يجب أن يكون :attribute رقمًا صحيحًا.',
    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'date' => ':attribute ليس تاريخًا صحيحًا.',
    'date_format' => 'لا يتوافق :attribute مع الصيغة :format.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'exists' => 'القيمة المحددة لـ :attribute غير صحيحة.',
    'unique' => 'قيمة :attribute مستخدمة من قبل.',
    'in' => 'القيمة المحددة لـ :attribute غير صحيحة.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على حروف وأرقام وشرطات فقط.',

    'between' => [
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون :attribute بين :min و :max حرفًا.',
        'array' => 'يجب أن يحتوي :attribute على عدد بين :min و :max عنصرًا.',
    ],
    'min' => [
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min حرفًا.',
        'array' => 'يجب أن يحتوي :attribute على :min عنصرًا على الأقل.',
    ],
    'max' => [
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max حرفًا.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصرًا.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'bio' => 'نبذة',
        'slug' => 'المعرّف',
        'is_active' => 'الحالة',
        'subject_ids' => 'المواد',
        'subject_id' => 'المادة',
        'student_id' => 'الطالب',
        'teacher_id' => 'المعلم',
        'education_level_ids' => 'المراحل الدراسية',
        'starts_at' => 'وقت البداية',
        'ends_at' => 'وقت النهاية',
        'day_of_week' => 'اليوم',
        'weeks' => 'عدد الأسابيع',
    ],
];
