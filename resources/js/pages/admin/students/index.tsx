import { Form, Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination, type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';

type Student = {
    id: number;
    name: string | null;
    phone: string | null;
    reservations_count: number;
};

export default function StudentsIndex({
    students,
}: {
    students: Paginated<Student>;
}) {
    return (
        <>
            <Head title="الطلاب" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="الطلاب"
                    description="قائمة الطلاب المسجلين بالاسم أو رقم الهاتف."
                    actions={
                        <Button asChild>
                            <Link href="/admin/students/create">
                                إضافة طالب
                            </Link>
                        </Button>
                    }
                />

                {students.data.length === 0 ? (
                    <EmptyState
                        title="لا يوجد طلاب بعد."
                        description="ابدأ بإضافة أول طالب."
                        action={
                            <Button asChild>
                                <Link href="/admin/students/create">
                                    إضافة طالب
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                    <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-start">
                                <tr>
                                    <th className="p-3 font-medium">الاسم</th>
                                    <th className="p-3 font-medium">
                                        رقم الهاتف
                                    </th>
                                    <th className="p-3 font-medium">الحجوزات</th>
                                    <th className="p-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {students.data.map((student) => (
                                    <tr
                                        key={student.id}
                                        className="border-t transition-colors hover:bg-muted/30"
                                    >
                                        <td className="p-3 font-medium">
                                            {student.name ?? '—'}
                                        </td>
                                        <td className="p-3 dir-ltr text-start">
                                            {student.phone ?? '—'}
                                        </td>
                                        <td className="p-3">
                                            {student.reservations_count}
                                        </td>
                                        <td className="p-3 text-end">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/admin/students/${student.id}/edit`}
                                                    >
                                                        تعديل
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/admin/students/${student.id}`}
                                                    method="delete"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            disabled={processing}
                                                        >
                                                            حذف
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination meta={students} />
                    </>
                )}
            </div>
        </>
    );
}
