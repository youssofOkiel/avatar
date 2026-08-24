import { Form, Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination } from '@/components/pagination';
import type { Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';

type Lecturer = {
    id: number;
    name: string;
    schedules_count: number;
};

export default function ExternalLecturersIndex({
    lecturers,
}: {
    lecturers: Paginated<Lecturer>;
}) {
    return (
        <>
            <Head title="محاضرون خارجيون" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="محاضرون خارجيون"
                    description="سجّل المحاضرين الخارجيين ومواعيدهم الأسبوعية."
                    actions={
                        <Button asChild>
                            <Link href="/admin/external-lecturers/create">
                                إضافة محاضر
                            </Link>
                        </Button>
                    }
                />

                {lecturers.data.length === 0 ? (
                    <EmptyState
                        title="لا يوجد محاضرون خارجيون بعد."
                        description="ابدأ بإضافة أول محاضر."
                        action={
                            <Button asChild>
                                <Link href="/admin/external-lecturers/create">
                                    إضافة محاضر
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
                                            عدد المواعيد
                                        </th>
                                        <th className="p-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {lecturers.data.map((lecturer) => (
                                        <tr
                                            key={lecturer.id}
                                            className="border-t transition-colors hover:bg-muted/30"
                                        >
                                            <td className="p-3 font-medium">
                                                {lecturer.name}
                                            </td>
                                            <td className="p-3">
                                                {lecturer.schedules_count}
                                            </td>
                                            <td className="p-3 text-end">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/external-lecturers/${lecturer.id}`}
                                                        >
                                                            التفاصيل
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/external-lecturers/${lecturer.id}/edit`}
                                                        >
                                                            تعديل
                                                        </Link>
                                                    </Button>
                                                    <Form
                                                        action={`/admin/external-lecturers/${lecturer.id}`}
                                                        method="delete"
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
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
                        <Pagination meta={lecturers} />
                    </>
                )}
            </div>
        </>
    );
}
